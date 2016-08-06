<?php

namespace Http\HttplugBundle\DependencyInjection;

use Http\Client\Common\BatchClient;
use Http\Client\Common\FlexibleHttpClient;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\HttpClientDiscovery;
use Http\HttplugBundle\ClientFactory\DummyClient;
use Http\HttplugBundle\ClientFactory\PluginClientFactory;
use Http\HttplugBundle\Collector\DebugPlugin;
use Http\Message\Authentication\BasicAuth;
use Http\Message\Authentication\Bearer;
use Http\Message\Authentication\Wsse;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
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
        if ($config['profiling']['enabled']) {
            $loader->load('data-collector.xml');

            if (!empty($config['profiling']['formatter'])) {
                // Add custom formatter
                $container
                    ->getDefinition('httplug.collector.debug_collector')
                    ->replaceArgument(0, new Reference($config['profiling']['formatter']))
                ;
            }

            $container
                ->getDefinition('httplug.formatter.full_http_message')
                ->addArgument($config['profiling']['captured_body_length'])
            ;
        }

        $this->configurePlugins($container, $config['plugins']);
        $this->configureClients($container, $config);
        $this->configureAutoDiscoveryClients($container, $config);
    }

    /**
     * Configure client services.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function configureClients(ContainerBuilder $container, array $config)
    {
        $first = null;

        foreach ($config['clients'] as $name => $arguments) {
            if ($first === null) {
                // Save the name of the first configurated client.
                $first = $name;
            }

            $this->configureClient($container, $name, $arguments, $config['profiling']['enabled']);
        }

        // If we have clients configured
        if ($first !== null) {
            // If we do not have a client named 'default'
            if (!isset($config['clients']['default'])) {
                // Alias the first client to httplug.client.default
                $container->setAlias('httplug.client.default', 'httplug.client.'.$first);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function configurePlugins(ContainerBuilder $container, array $config)
    {
        if (!empty($config['authentication'])) {
            $this->configureAuthentication($container, $config['authentication']);
        }
        unset($config['authentication']);

        foreach ($config as $name => $pluginConfig) {
            $pluginId = 'httplug.plugin.'.$name;

            if ($pluginConfig['enabled']) {
                $def = $container->getDefinition($pluginId);
                $this->configurePluginByName($name, $def, $pluginConfig);
            } else {
                $container->removeDefinition($pluginId);
            }
        }
    }

    /**
     * @param string     $name
     * @param Definition $definition
     * @param array      $config
     */
    private function configurePluginByName($name, Definition $definition, array $config)
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
                $definition->addArgument($config['use_content_encoding']);
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
                $definition
                    ->addArgument($config['preserve_header'])
                    ->addArgument($config['use_default_for_multiple']);
                break;
            case 'retry':
                $definition->addArgument($config['retry']);
                break;
            case 'stopwatch':
                $definition->replaceArgument(0, new Reference($config['stopwatch']));
                break;
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function configureAuthentication(ContainerBuilder $container, array $config)
    {
        foreach ($config as $name => $values) {
            $authServiceKey = sprintf('httplug.plugin.authentication.%s.auth', $name);
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

            $container->register('httplug.plugin.authentication.'.$name, AuthenticationPlugin::class)
                ->addArgument(new Reference($authServiceKey));
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $name
     * @param array            $arguments
     * @param bool             $profiling
     */
    private function configureClient(ContainerBuilder $container, $name, array $arguments, $profiling)
    {
        $serviceId = 'httplug.client.'.$name;

        $plugins = $arguments['plugins'];
        $pluginClientOptions = [];

        if ($profiling) {
            if (!in_array('httplug.plugin.stopwatch', $arguments['plugins'])) {
                // Add the stopwatch plugin
                array_unshift($arguments['plugins'], 'httplug.plugin.stopwatch');
            }

            // Tell the plugin journal what plugins we used
            $container
                ->getDefinition('httplug.collector.plugin_journal')
                ->addMethodCall('setPlugins', [$name, $arguments['plugins']])
            ;

            $debugPluginServiceId = $this->registerDebugPlugin($container, $serviceId);

            $pluginClientOptions['debug_plugins'] = [new Reference($debugPluginServiceId)];
        }

        if (array_key_exists('options', $arguments)) {
            foreach ($arguments['options'] as $option => $value) {
                switch ($option) {
                    case 'default_host':
                        $uriService = $serviceId.'.default_host_uri';
                        $addHostPlugin = $serviceId.'.default_host_plugin';
                        $this->createUri($container, $uriService, $value);
                        $container
                            ->register($addHostPlugin, AddHostPlugin::class)
                            ->setPublic(false)
                            ->addArgument(new Reference($uriService))
                        ;
                        // add to end of plugins list unless explicitly configured
                        if (!in_array($addHostPlugin, $plugins)) {
                            $plugins[] = $addHostPlugin;
                        }
                        break;
                    case 'force_host':
                        $uriService = $serviceId.'.force_host_uri';
                        $addHostPlugin = $serviceId.'.force_host_plugin';
                        $this->createUri($container, $uriService, $value);
                        $container
                            ->register($addHostPlugin, AddHostPlugin::class)
                            ->setPublic(false)
                            ->addArgument(new Reference($uriService))
                            ->addArgument(['replace' => true])
                        ;
                        // add to end of plugins list unless explicitly configured
                        if (!in_array($addHostPlugin, $plugins)) {
                            $plugins[] = $addHostPlugin;
                        }
                        break;
                }
            }
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
                    [HttpClientDiscovery::class, 'find'],
                    $config['profiling']['enabled']
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
                    [HttpAsyncClientDiscovery::class, 'find'],
                    $config['profiling']['enabled']
                );
            }

            $asyncHttpClient = new Reference($asyncHttpClient);
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
     * @param ContainerBuilder $container
     * @param string           $name
     * @param callable         $factory
     * @param bool             $profiling
     *
     * @return string service id
     */
    private function registerAutoDiscoverableClient(ContainerBuilder $container, $name, $factory, $profiling)
    {
        $serviceId = 'httplug.auto_discovery.'.$name;

        $pluginClientOptions = [];

        if ($profiling) {
            // Tell the plugin journal what plugins we used
            $container
                ->getDefinition('httplug.collector.plugin_journal')
                ->addMethodCall('setPlugins', [$name, ['httplug.plugin.stopwatch']])
            ;

            $debugPluginServiceId = $this->registerDebugPlugin($container, $serviceId);

            $pluginClientOptions['debug_plugins'] = [new Reference($debugPluginServiceId)];
        }

        $container
            ->register($serviceId, DummyClient::class)
            ->setFactory([PluginClientFactory::class, 'createPluginClient'])
            ->setArguments([[new Reference('httplug.plugin.stopwatch')], $factory, [], $pluginClientOptions])
        ;

        return $serviceId;
    }

    /**
     * Create a new plugin service for this client.
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId
     *
     * @return string
     */
    private function registerDebugPlugin(ContainerBuilder $container, $serviceId)
    {
        $serviceIdDebugPlugin = $serviceId.'.debug_plugin';

        $container
            ->register($serviceIdDebugPlugin, DebugPlugin::class)
            ->addArgument(new Reference('httplug.collector.debug_collector'))
            ->addArgument(substr($serviceId, strrpos($serviceId, '.') + 1))
            ->setPublic(false)
        ;

        return $serviceIdDebugPlugin;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }
}
