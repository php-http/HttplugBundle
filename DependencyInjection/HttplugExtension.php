<?php

namespace Http\HttplugBundle\DependencyInjection;

use Http\HttplugBundle\ClientFactory\DummyClient;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author David Buchmann <mail@davidbu.ch>
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
        foreach ($config['classes'] as $service => $class) {
            if (!empty($class)) {
                $container->removeDefinition(sprintf('httplug.%s.default', $service));
                $container->register(sprintf('httplug.%s.default', $service), $class);
            }
        }

        foreach ($config['main_alias'] as $type => $id) {
            $container->setAlias(sprintf('httplug.%s', $type), $id);
        }
        $this->configureClients($container, $config);
    }

    /**
     * Configure client services.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    protected function configureClients(ContainerBuilder $container, array $config)
    {
        $first = isset($config['clients']['default']) ? 'default' : null;
        foreach ($config['clients'] as $name => $arguments) {
            if ($first === null) {
                $first = $name;
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
        }
    }
}
