<?php

namespace Http\HttplugBundle\DependencyInjection;

use Http\Client\Plugin\AuthenticationPlugin;
use Http\Client\Plugin\PluginClient;
use Http\HttplugBundle\ClientFactory\DummyClient;
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

        $enabled = is_bool($config['toolbar']['enabled']) ? $config['toolbar']['enabled'] : $container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug');
        if ($enabled) {
            $loader->load('data-collector.xml');
            $config['_inject_collector_plugin'] = true;

            if (!empty($config['toolbar']['formatter'])) {
                $container->getDefinition('httplug.collector.message_journal')
                    ->replaceArgument(0, new Reference($config['toolbar']['formatter']));
            }
        }

        $useDiscovery = false;
        foreach ($config['classes'] as $service => $class) {
            if (!empty($class)) {
                $container->register(sprintf('httplug.%s.default', $service), $class);
            } else {
                // we have to use discovery to find this class
                $useDiscovery = true;
            }
        }

        if ($useDiscovery) {
            $this->verifyDiscoveryInstalled($container);
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

        // If we have clients configured
        if ($first !== null) {
            if ($first !== 'default') {
                // Alias the first client to httplug.client.default
                $container->setAlias('httplug.client.default', 'httplug.client.'.$first);
            }
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
     * Verify that Puli is installed
     *
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    private function verifyDiscoveryInstalled(ContainerBuilder $container)
    {
        $enabledBundles = $container->getParameter('kernel.bundles');
        if (!isset($enabledBundles['PuliSymfonyBundle'])) {
            throw new \Exception(
                'You need to install puli/symfony-bundle or add configuration at httplug.classes in order to use this bundle. Refer to http://some.doc'
            );
        }

        // .... OR
        if (defined('PULI_FACTORY')) {
            // Throw exception
        }
    }
}
