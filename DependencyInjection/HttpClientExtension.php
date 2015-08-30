<?php

/*
 * This file is part of the Http Client bundle.
 *
 * (c) David Buchmann <mail@davidbu.ch>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Http\ClientBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * {@inheritdoc}
 */
class HttpClientExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('discovery.xml');
        foreach ($config['classes'] as $service => $class) {
            if ($class) {
                $container->removeDefinition(sprintf('http_client.%s.default', $service));
                $container->register(sprintf('http_client.%s.default', $service), $class);
            }
        }

        foreach ($config['main_alias'] as $type => $id) {
            $container->setAlias(sprintf('http_client.%s', $type), $id);
        }
    }
}
