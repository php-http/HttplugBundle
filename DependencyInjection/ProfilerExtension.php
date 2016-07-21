<?php

namespace Http\HttplugBundle\DependencyInjection;

use Http\HttplugBundle\Collector\DebugPlugin;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This class is responsible for loading profiler tools and the toolbar. This extension should generally not be used
 * in production.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ProfilerExtension
{
    /**
     * @param array            $config           Processed configuration
     * @param ContainerBuilder $container
     * @param array            $clientServiceIds ids of all clients
     */
    public function load(array $config, ContainerBuilder $container, array $clientServiceIds)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('data-collector.xml');

        if (!empty($config['toolbar']['formatter'])) {
            // Add custom formatter
            $container->getDefinition('httplug.collector.debug_collector')
                ->replaceArgument(0, new Reference($config['toolbar']['formatter']));
        }

        $container->getDefinition('httplug.formatter.full_http_message')
            ->addArgument($config['toolbar']['captured_body_length']);

        foreach ($clientServiceIds as $clientId) {
            $pluginClientDefinition = $container->getDefinition($clientId);
            $serviceIdDebugPlugin = $this->registerDebugPlugin($container, $clientId);

            $argument = $this->mergeDebugPluginArguments([new Reference($serviceIdDebugPlugin)], $pluginClientDefinition->getArgument(3));
            $pluginClientDefinition->replaceArgument(3, $argument);
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
        $serviceIdDebugPlugin = $name.'.debug_plugin';
        $container->register($serviceIdDebugPlugin, DebugPlugin::class)
            ->addArgument(new Reference('httplug.collector.debug_collector'))
            ->addArgument(substr($name, strrpos($name, '.') + 1))
            ->setPublic(false);

        return $serviceIdDebugPlugin;
    }

    /**
     * @param array $newArgument
     * @param array $existing
     *
     * @return array
     */
    private function mergeDebugPluginArguments(array $newArgument, array $existing = [])
    {
        if (empty($existing)) {
            $mergedArgument = ['debug_plugins' => $newArgument];
        } elseif (empty($existing['debug_plugins'])) {
            $mergedArgument['debug_plugins'] = $newArgument;
        } else {
            $mergedArgument['debug_plugins'] = array_merge($existing['debug_plugins'], $newArgument);
        }

        return $mergedArgument;
    }
}
