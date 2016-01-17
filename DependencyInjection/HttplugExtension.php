<?php

namespace Http\HttplugBundle\DependencyInjection;

use Http\Client\Plugin\PluginClient;
use Http\HttplugBundle\ClientFactory\DummyClient;
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
        $loader->load('discovery.xml');

        $enabled = is_bool($config['toolbar']['enabled']) ? $config['toolbar']['enabled'] : $container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug');
        if ($enabled) {
            $loader->load('data-collector.xml');
            $config['_inject_collector_plugin'] = true;

            if (!empty($config['toolbar']['formatter'])) {
                $container->getDefinition('httplug.collector.message_journal')
                    ->replaceArgument(0, new Reference($config['toolbar']['formatter']));
            }
        }

        foreach ($config['classes'] as $service => $class) {
            if (!empty($class)) {
                $container->removeDefinition(sprintf('httplug.%s.default', $service));
                $container->register(sprintf('httplug.%s.default', $service), $class);
            }
        }

        foreach ($config['main_alias'] as $type => $id) {
            $container->setAlias(sprintf('httplug.%s', $type), $id);
        }
        $this->configurePlugins($container, $config['plugins']);
        $this->configureClients($container, $config);
    }

    /**
     * Configure client services.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function configureClients(ContainerBuilder $container, array $config)
    {
        $first = isset($config['clients']['default']) ? 'default' : null;
        foreach ($config['clients'] as $name => $arguments) {
            if ($first === null) {
                $first = $name;
            }

            if (isset($config['_inject_collector_plugin'])) {
                array_unshift($arguments['plugins'], 'httplug.collector.history_plugin');
            }

            $def = $container->register('httplug.client.'.$name, DummyClient::class);

            if (empty($arguments['plugins'])) {
                $def->setFactory([new Reference($arguments['factory']), 'createClient'])
                    ->addArgument($arguments['config']);
            } else {
                $def->setFactory('Http\HttplugBundle\ClientFactory\PluginClientFactory::createPluginClient')
                    ->addArgument(array_map(function ($id) {
                        return new Reference($id);
                    }, $arguments['plugins']))
                    ->addArgument(new Reference($arguments['factory']))
                    ->addArgument($arguments['config']);
            }
        }

        // Alias the first client to httplug.client.default
        if ($first !== null) {
            $container->setAlias('httplug.client.default', 'httplug.client.'.$first);
        } elseif (isset($config['_inject_collector_plugin'])) {
            // No client was configured. Make sure to inject history plugin to the auto discovery client.
            $container->register('httplug.client', PluginClient::class)
                ->addArgument(new Reference('httplug.client.default'))
                ->addArgument([new Reference('httplug.collector.history_plugin')]);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function configurePlugins(ContainerBuilder $container, array $config)
    {
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
            case 'authentication':
                $definition->replaceArgument(0, new Reference($config['authentication']));
                break;
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
}
