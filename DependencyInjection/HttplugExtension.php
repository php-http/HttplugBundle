<?php

namespace Http\HttplugBundle\DependencyInjection;

use Http\Client\Common\BatchClient;
use Http\Client\Common\FlexibleHttpClient;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\HttpClientDiscovery;
use Http\HttplugBundle\ClientFactory\DummyClient;
use Http\HttplugBundle\ClientFactory\PluginClientFactory;
use Http\HttplugBundle\Collector\ProfileClientFactory;
use Http\HttplugBundle\Collector\ProfilePlugin;
use Http\Message\Authentication\BasicAuth;
use Http\Message\Authentication\Bearer;
use Http\Message\Authentication\Wsse;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author David Buchmann <mail@davidbu.ch>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class HttplugExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.xml');
        $loader->load('plugins.xml');

        // Register default services
        foreach ($config['classes'] as $service => $class) {
            if (!empty($class)) {
                $container->register(sprintf('httplug.%s.default', $service), $class);
            }
        }

        // Set main aliases
        foreach ($config['main_alias'] as $type => $id) {
            $container->setAlias(sprintf('httplug.%s', $type), $id);
        }

        // Configure toolbar
        $profilingEnabled = $this->isConfigEnabled($container, $config['profiling']);
        if ($profilingEnabled) {
            $loader->load('data-collector.xml');

            if (!empty($config['profiling']['formatter'])) {
                // Add custom formatter
                $container
                    ->getDefinition('httplug.collector.formatter')
                    ->replaceArgument(0, new Reference($config['profiling']['formatter']))
                ;
            }

            $container
                ->getDefinition('httplug.formatter.full_http_message')
                ->addArgument($config['profiling']['captured_body_length'])
            ;
        }

        $this->configureClients($container, $config, $profilingEnabled);
        $this->configureSharedPlugins($container, $config['plugins']); // must be after clients, as clients.X.plugins might use plugins as templates that will be removed
        $this->configureAutoDiscoveryClients($container, $config);
    }

    /**
     * Configure client services.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     * @param bool             $profiling
     */
    private function configureClients(ContainerBuilder $container, array $config, $profiling)
    {
        $first = null;
        $clients = [];

        foreach ($config['clients'] as $name => $arguments) {
            if ($first === null) {
                // Save the name of the first configured client.
                $first = $name;
            }

            $this->configureClient($container, $name, $arguments, $this->isConfigEnabled($container, $config['profiling']));
            $clients[] = $name;
        }

        // If we have clients configured
        if ($first !== null) {
            // If we do not have a client named 'default'
            if (!isset($config['clients']['default'])) {
                // Alias the first client to httplug.client.default
                $container->setAlias('httplug.client.default', 'httplug.client.'.$first);
            }
        }

        if ($profiling) {
            $container->getDefinition('httplug.collector.collector')
                ->setArguments([$clients])
            ;
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function configureSharedPlugins(ContainerBuilder $container, array $config)
    {
        if (!empty($config['authentication'])) {
            $this->configureAuthentication($container, $config['authentication']);
        }
        unset($config['authentication']);

        foreach ($config as $name => $pluginConfig) {
            $pluginId = 'httplug.plugin.'.$name;

            if ($this->isConfigEnabled($container, $pluginConfig)) {
                $def = $container->getDefinition($pluginId);
                $this->configurePluginByName($name, $def, $pluginConfig, $container, $pluginId);
            } else {
                $container->removeDefinition($pluginId);
            }
        }
    }

    /**
     * @param string           $name
     * @param Definition       $definition
     * @param array            $config
     * @param ContainerBuilder $container  In case we need to add additional services for this plugin
     * @param string           $serviceId  Service id of the plugin, in case we need to add additional services for this plugin.
     */
    private function configurePluginByName($name, Definition $definition, array $config, ContainerInterface $container, $serviceId)
    {
        switch ($name) {
            case 'cache':
                $definition
                    ->replaceArgument(0, new Reference($config['cache_pool']))
                    ->replaceArgument(1, new Reference($config['stream_factory']))
                    ->replaceArgument(2, $config['config']);
                break;
            case 'cookie':
                $definition->replaceArgument(0, new Reference($config['cookie_jar']));
                break;
            case 'decoder':
                $definition->addArgument([
                    'use_content_encoding' => $config['use_content_encoding'],
                ]);
                break;
            case 'history':
                $definition->replaceArgument(0, new Reference($config['journal']));
                break;
            case 'logger':
                $definition->replaceArgument(0, new Reference($config['logger']));
                if (!empty($config['formatter'])) {
                    $definition->replaceArgument(1, new Reference($config['formatter']));
                }
                break;
            case 'redirect':
                $definition->addArgument([
                    'preserve_header' => $config['preserve_header'],
                    'use_default_for_multiple' => $config['use_default_for_multiple'],
                ]);
                break;
            case 'retry':
                $definition->addArgument([
                    'retries' => $config['retry'],
                ]);
                break;
            case 'stopwatch':
                $definition->replaceArgument(0, new Reference($config['stopwatch']));
                break;

            /* client specific plugins */

            case 'add_host':
                $uriService = $serviceId.'.host_uri';
                $this->createUri($container, $uriService, $config['host']);
                $definition->replaceArgument(0, new Reference($uriService));
                $definition->replaceArgument(1, [
                    'replace' => $config['replace'],
                ]);
                break;
            case 'header_append':
            case 'header_defaults':
            case 'header_set':
            case 'header_remove':
                $definition->replaceArgument(0, $config['headers']);
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Internal exception: Plugin %s is not handled', $name));
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @return array List of service ids for the authentication plugins.
     */
    private function configureAuthentication(ContainerBuilder $container, array $config, $servicePrefix = 'httplug.plugin.authentication')
    {
        $pluginServices = [];

        foreach ($config as $name => $values) {
            $authServiceKey = sprintf($servicePrefix.'.%s.auth', $name);
            switch ($values['type']) {
                case 'bearer':
                    $container->register($authServiceKey, Bearer::class)
                        ->addArgument($values['token']);
                    break;
                case 'basic':
                    $container->register($authServiceKey, BasicAuth::class)
                        ->addArgument($values['username'])
                        ->addArgument($values['password']);
                    break;
                case 'wsse':
                    $container->register($authServiceKey, Wsse::class)
                        ->addArgument($values['username'])
                        ->addArgument($values['password']);
                    break;
                case 'service':
                    $authServiceKey = $values['service'];
                    break;
                default:
                    throw new \LogicException(sprintf('Unknown authentication type: "%s"', $values['type']));
            }

            $pluginServiceKey = $servicePrefix.'.'.$name;
            $container->register($pluginServiceKey, AuthenticationPlugin::class)
                ->addArgument(new Reference($authServiceKey))
            ;
            $pluginServices[] = $pluginServiceKey;
        }

        return $pluginServices;
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $clientName
     * @param array            $arguments
     * @param bool             $profiling
     */
    private function configureClient(ContainerBuilder $container, $clientName, array $arguments, $profiling)
    {
        $serviceId = 'httplug.client.'.$clientName;

        $plugins = [];
        foreach ($arguments['plugins'] as $plugin) {
            list($pluginName, $pluginConfig) = each($plugin);
            if ('reference' === $pluginName) {
                $plugins[] = $pluginConfig['id'];
            } elseif ('authentication' === $pluginName) {
                $plugins = array_merge($plugins, $this->configureAuthentication($container, $pluginConfig, $serviceId.'.authentication'));
            } else {
                $plugins[] = $this->configurePlugin($container, $serviceId, $pluginName, $pluginConfig);
            }
        }

        $pluginClientOptions = [];
        if ($profiling) {
            //Decorate each plugin with a ProfilePlugin instance.
            foreach ($plugins as $pluginServiceId) {
                $this->decoratePluginWithProfilePlugin($container, $pluginServiceId);
            }

            // To profile the requests, add a StackPlugin as first plugin in the chain.
            $stackPluginId = $this->configureStackPlugin($container, $clientName, $serviceId);
            array_unshift($plugins, $stackPluginId);
        }

        $container
            ->register($serviceId, DummyClient::class)
            ->setFactory([PluginClientFactory::class, 'createPluginClient'])
            ->addArgument(
                array_map(
                    function ($id) {
                        return new Reference($id);
                    },
                    $plugins
                )
            )
            ->addArgument(new Reference($arguments['factory']))
            ->addArgument($arguments['config'])
            ->addArgument($pluginClientOptions)
        ;

        /*
         * Decorate the client with clients from client-common
         */
        if ($arguments['flexible_client']) {
            $container
                ->register($serviceId.'.flexible', FlexibleHttpClient::class)
                ->addArgument(new Reference($serviceId.'.flexible.inner'))
                ->setPublic(false)
                ->setDecoratedService($serviceId)
            ;
        }

        if ($arguments['http_methods_client']) {
            $container
                ->register($serviceId.'.http_methods', HttpMethodsClient::class)
                ->setArguments([new Reference($serviceId.'.http_methods.inner'), new Reference('httplug.message_factory')])
                ->setPublic(false)
                ->setDecoratedService($serviceId)
            ;
        }

        if ($arguments['batch_client']) {
            $container
                ->register($serviceId.'.batch_client', BatchClient::class)
                ->setArguments([new Reference($serviceId.'.batch_client.inner')])
                ->setPublic(false)
                ->setDecoratedService($serviceId)
            ;
        }
    }

    /**
     * Create a URI object with the default URI factory.
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId Name of the private service to create
     * @param string           $uri       String representation of the URI
     */
    private function createUri(ContainerBuilder $container, $serviceId, $uri)
    {
        $container
            ->register($serviceId, UriInterface::class)
            ->setPublic(false)
            ->setFactory([new Reference('httplug.uri_factory'), 'createUri'])
            ->addArgument($uri)
        ;
    }

    /**
     * Make the user can select what client is used for auto discovery. If none is provided, a service will be created
     * by finding a client using auto discovery.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function configureAutoDiscoveryClients(ContainerBuilder $container, array $config)
    {
        $httpClient = $config['discovery']['client'];

        if (!empty($httpClient)) {
            if ($httpClient === 'auto') {
                $httpClient = $this->registerAutoDiscoverableClient(
                    $container,
                    'auto_discovered_client',
                    $this->configureAutoDiscoveryFactory(
                        $container,
                        HttpClientDiscovery::class,
                        'auto_discovered_client',
                        $config
                    ),
                    $this->isConfigEnabled($container, $config['profiling'])
                );
            }

            $httpClient = new Reference($httpClient);
        }

        $asyncHttpClient = $config['discovery']['async_client'];

        if (!empty($asyncHttpClient)) {
            if ($asyncHttpClient === 'auto') {
                $asyncHttpClient = $this->registerAutoDiscoverableClient(
                    $container,
                    'auto_discovered_async',
                    $this->configureAutoDiscoveryFactory(
                        $container,
                        HttpAsyncClientDiscovery::class,
                        'auto_discovered_async',
                        $config
                    ),
                    $this->isConfigEnabled($container, $config['profiling'])
                );
            }

            $asyncHttpClient = new Reference($asyncHttpClient);
        }

        if (null === $httpClient && null === $asyncHttpClient) {
            $container->removeDefinition('httplug.strategy');

            return;
        }

        $container
            ->getDefinition('httplug.strategy')
            ->addArgument($httpClient)
            ->addArgument($asyncHttpClient)
        ;
    }

    /**
     * Find a client with auto discovery and return a service Reference to it.
     *
     * @param ContainerBuilder   $container
     * @param string             $name
     * @param Reference|callable $factory
     * @param bool               $profiling
     *
     * @return string service id
     */
    private function registerAutoDiscoverableClient(ContainerBuilder $container, $name, $factory, $profiling)
    {
        $serviceId = 'httplug.auto_discovery.'.$name;

        $plugins = [];
        if ($profiling) {
            // To profile the requests, add a StackPlugin as first plugin in the chain.
            $plugins[] = $this->configureStackPlugin($container, $name, $serviceId);
        }

        $container
            ->register($serviceId, DummyClient::class)
            ->setFactory([PluginClientFactory::class, 'createPluginClient'])
            ->setArguments([
                array_map(
                    function ($id) {
                        return new Reference($id);
                    },
                    $plugins
                ),
                $factory,
                [],
            ])
        ;

        if ($profiling) {
            $collector = $container->getDefinition('httplug.collector.collector');
            $collector->replaceArgument(0, array_merge($collector->getArgument(0), [$name]));
        }

        return $serviceId;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    /**
     * Configure a plugin using the parent definition from plugins.xml.
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId
     * @param string           $pluginName
     * @param array            $pluginConfig
     *
     * @return string configured service id
     */
    private function configurePlugin(ContainerBuilder $container, $serviceId, $pluginName, array $pluginConfig)
    {
        $pluginServiceId = $serviceId.'.plugin.'.$pluginName;

        $definition = class_exists(ChildDefinition::class)
            ? new ChildDefinition('httplug.plugin.'.$pluginName)
            : new DefinitionDecorator('httplug.plugin.'.$pluginName);

        $this->configurePluginByName($pluginName, $definition, $pluginConfig, $container, $pluginServiceId);
        $container->setDefinition($pluginServiceId, $definition);

        return $pluginServiceId;
    }

    /**
     * Decorate the plugin service with a ProfilePlugin service.
     *
     * @param ContainerBuilder $container
     * @param string           $pluginServiceId
     */
    private function decoratePluginWithProfilePlugin(ContainerBuilder $container, $pluginServiceId)
    {
        $container->register($pluginServiceId.'.debug', ProfilePlugin::class)
            ->setDecoratedService($pluginServiceId)
            ->setArguments([
                new Reference($pluginServiceId.'.debug.inner'),
                new Reference('httplug.collector.collector'),
                new Reference('httplug.collector.formatter'),
                $pluginServiceId,
            ])
            ->setPublic(false);
    }

    /**
     * Configure a StackPlugin for a client.
     *
     * @param ContainerBuilder $container
     * @param string           $clientName Client name to display in the profiler.
     * @param string           $serviceId  Client service id. Used as base for the StackPlugin service id.
     *
     * @return string configured StackPlugin service id
     */
    private function configureStackPlugin(ContainerBuilder $container, $clientName, $serviceId)
    {
        $pluginServiceId = $serviceId.'.plugin.stack';

        $definition = class_exists(ChildDefinition::class)
            ? new ChildDefinition('httplug.plugin.stack')
            : new DefinitionDecorator('httplug.plugin.stack');

        $definition->addArgument($clientName);
        $container->setDefinition($pluginServiceId, $definition);

        return $pluginServiceId;
    }

    /**
     * Configure the discovery factory when profiling is enabled to get client decorated with a ProfileClient.
     *
     * @param ContainerBuilder $container
     * @param string           $discovery
     * @param string           $name
     * @param array            $config
     *
     * @return callable|Reference
     */
    private function configureAutoDiscoveryFactory(ContainerBuilder $container, $discovery, $name, array $config)
    {
        $factory = [$discovery, 'find'];
        if ($this->isConfigEnabled($container, $config['profiling'])) {
            $factoryServiceId = 'httplug.auto_discovery.'.$name.'.factory';
            $container->register($factoryServiceId, ProfileClientFactory::class)
                ->setPublic(false)
                ->setArguments([
                    $factory,
                    new Reference('httplug.collector.collector'),
                    new Reference('httplug.collector.formatter'),
                    new Reference('debug.stopwatch'),
                ]);
            $factory = new Reference($factoryServiceId);
        }

        return $factory;
    }
}
