<?php

namespace Http\HttplugBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('httplug');

        $rootNode
            ->validate()
                ->ifTrue(function ($v) {
                    return !empty($v['classes']['client'])
                        || !empty($v['classes']['message_factory'])
                        || !empty($v['classes']['uri_factory'])
                    ;
                })
                ->then(function ($v) {
                    foreach ($v['classes'] as $key => $class) {
                        if (null !== $class && !class_exists($class)) {
                            throw new InvalidConfigurationException(sprintf(
                                'Class %s specified for httplug.classes.%s does not exist.',
                                $class,
                                $key
                            ));
                        }
                    }

                    return $v;
                })
            ->end()
            ->children()
                ->arrayNode('main_alias')
                    ->addDefaultsIfNotSet()
                    ->info('Configure which service the main alias point to.')
                    ->children()
                        ->scalarNode('client')->defaultValue('httplug.client.default')->end()
                        ->scalarNode('message_factory')->defaultValue('httplug.message_factory.default')->end()
                        ->scalarNode('uri_factory')->defaultValue('httplug.uri_factory.default')->end()
                    ->end()
                ->end()
                ->arrayNode('classes')
                    ->addDefaultsIfNotSet()
                    ->info('Overwrite a service class instead of using the discovery mechanism.')
                    ->children()
                        ->scalarNode('client')->defaultNull()->end()
                        ->scalarNode('message_factory')->defaultNull()->end()
                        ->scalarNode('uri_factory')->defaultNull()->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
