<?php

namespace Http\HttplugBundle\DependencyInjection;

use Http\Client\Common\FlexibleHttpClient;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\HttpClientDiscovery;
use Http\HttplugBundle\ClientFactory\DummyClient;
use Http\HttplugBundle\Collector\DebugPlugin;
use Http\Message\Authentication\BasicAuth;
use Http\Message\Authentication\Bearer;
use Http\Message\Authentication\Wsse;
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

        $toolbar = is_bool($config['toolbar']['enabled']) ? $config['toolbar']['enabled'] : $container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug');

        if ($toolbar) {
            $loader->load('data-collector.xml');

            if (!empty($config['toolbar']['formatter'])) {
                // Add custom formatter
                $container->getDefinition('httplug.collector.debug_collector')
                    ->replaceArgument(0, new Reference($config['toolbar']['formatter']));
            }

            $container->getDefinition('httplug.formatter.full_http_message')
                ->addArgument($config['toolbar']['captured_body_length']);
        }

        foreach ($config['classes'] as $service => $class) {
            if (!empty($class)) {
                $container->register(sprintf('httplug.%s.default', $service), $class);
            }
        }

        // Set main aliases
        foreach ($config['main_alias'] as $type => $id) {
            $container->setAlias(sprintf('httplug.%s', $type), $id);
        }

        $this->configurePlugins($container, $config['plugins']);
        $this->configureClients($container, $config, $toolbar);
        $this->configureAutoDiscoveryClients($container, $config, $toolbar);
    }

    /**
     * Configure client services.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     * @param bool             $enableCollector
     */
    private function configureClients(ContainerBuilder $container, array $config, $enableCollector)
    {
        // If we have a client named 'default'
        $first = isset($config['clients']['default']) ? 'default' : null;

        foreach ($config['clients'] as $name => $arguments) {
            if ($first === null) {
                // Save the name of the first configurated client.
                $first = $name;
            }

            $this->configureClient($container, $name, $arguments, $enableCollector);
        }

        // If we have clients configured
        if ($first !== null) {
            if ($first !== 'default') {
                // Alias the first client to httplug.client.default
                $container->setAlias('httplug.client.default', 'httplug.client.'.$first);
            }
        } elseif ($enableCollector) {
            $serviceIdDebugPlugin = $this->registerDebugPlugin($container, 'default');
            // No client was configured. Make sure to configure the auto discovery client with the PluginClient.
            $container->register('httplug.client', PluginClient::class)
                ->addArgument(new Reference('httplug.client.default'))
                ->addArgument([])
                ->addArgument(['debug_plugins' => [new Reference($serviceIdDebugPlugin)]]);
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
     * @param bool             $enableCollector
     */
    private function configureClient(ContainerBuilder $container, $name, array $arguments, $enableCollector)
    {
        $serviceId = 'httplug.client.'.$name;
        $def = $container->register($serviceId, DummyClient::class);

        // If there are no plugins nor should we use the data collector
        if (empty($arguments['plugins']) && !$enableCollector) {
            $def->setFactory([new Reference($arguments['factory']), 'createClient'])
                ->addArgument($arguments['config']);
        } else {
            $def
                ->setFactory('Http\HttplugBundle\ClientFactory\PluginClientFactory::createPluginClient')
                ->addArgument(
                    array_map(
                        function ($id) {
                            return new Reference($id);
                        },
                        $arguments['plugins']
                    )
                )
                ->addArgument(new Reference($arguments['factory']))
                ->addArgument($arguments['config'])
            ;

            if ($enableCollector) {
                $serviceIdDebugPlugin = $this->registerDebugPlugin($container, $name);
                $def->addArgument(['debug_plugins' => [new Reference($serviceIdDebugPlugin)]]);

                // tell the plugin journal what plugins we used
                $container->getDefinition('httplug.collector.plugin_journal')
                    ->addMethodCall('setPlugins', [$name, $arguments['plugins']]);
            }
        }

        /*
         * Decorate the client with clients from client-common
         */

        if ($arguments['flexible_client']) {
            $container->register($serviceId.'.flexible', FlexibleHttpClient::class)
                ->addArgument(new Reference($serviceId.'.flexible.inner'))
                ->setPublic(false)
                ->setDecoratedService($serviceId);
        }

        if ($arguments['http_methods_client']) {
            $container->register($serviceId.'.http_methods', HttpMethodsClient::class)
                ->setArguments([new Reference($serviceId.'.http_methods.inner'), new Reference('httplug.message_factory')])
                ->setPublic(false)
                ->setDecoratedService($serviceId);
        }
    }

    /**
     * Create a new plugin service for this client.
     *
     * @param ContainerBuilder $container
     * @param string           $name
     *
     * @return string
     */
    private function registerDebugPlugin(ContainerBuilder $container, $name)
    {
        $serviceIdDebugPlugin = 'httplug.client.'.$name.'.debug_plugin';
        $container->register($serviceIdDebugPlugin, DebugPlugin::class)
            ->addArgument(new Reference('httplug.collector.debug_collector'))
            ->addArgument($name)
            ->setPublic(false);

        return $serviceIdDebugPlugin;
    }

    /**
     * Make sure we inject the debug plugin for clients found by auto discovery.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     * @param bool             $enableCollector
     */
    private function configureAutoDiscoveryClients(ContainerBuilder $container, array $config, $enableCollector)
    {
        $httpClient = $config['discovery']['client'];
        if ($httpClient === 'auto') {
            $httpClient = $this->registerAutoDiscoverableClientWithDebugPlugin(
                $container,
                'client',
                [HttpClientDiscovery::class, 'find'],
                $enableCollector
            );
        } elseif ($httpClient) {
            $httpClient = new Reference($httpClient);
        }

        $asyncHttpClient = $config['discovery']['async_client'];
        if ($asyncHttpClient === 'auto') {
            $asyncHttpClient = $this->registerAutoDiscoverableClientWithDebugPlugin(
                $container,
                'async_client',
                [HttpAsyncClientDiscovery::class, 'find'],
                $enableCollector
            );
        } elseif ($asyncHttpClient) {
            $asyncHttpClient = new Reference($httpClient);
        }

        $container->getDefinition('httplug.strategy')
            ->addArgument($httpClient)
            ->addArgument($asyncHttpClient);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $name
     * @param callable         $factory
     * @param bool             $enableCollector
     *
     * @return Reference
     */
    private function registerAutoDiscoverableClientWithDebugPlugin(ContainerBuilder $container, $name, $factory, $enableCollector)
    {
        $definition = $container->register('httplug.auto_discovery_'.$name.'.pure', DummyClient::class);
        $definition->setPublic(false);
        $definition->setFactory($factory);

        $pluginDefinition = $container
            ->register('httplug.auto_discovery_'.$name.'.plugin', PluginClient::class)
            ->setPublic(false)
            ->addArgument(new Reference('httplug.auto_discovery_'.$name.'.pure'))
            ->addArgument([])
        ;

        if ($enableCollector) {
            $serviceIdDebugPlugin = $this->registerDebugPlugin($container, 'auto_discovery_'.$name);
            $pluginDefinition->addArgument(['debug_plugins' => [new Reference($serviceIdDebugPlugin)]]);
        }

        return new Reference('httplug.auto_discovery_'.$name.'.plugin');
    }
}
