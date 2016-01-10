<?php

namespace Http\HttplugBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This class contains the configuration information for the bundle.
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

        $this->configureClients($rootNode);

        $rootNode
            ->validate()
                ->ifTrue(function ($v) {
                    return !empty($v['classes']['client'])
                        || !empty($v['classes']['message_factory'])
                        || !empty($v['classes']['uri_factory'])
                        || !empty($v['classes']['stream_factory']);
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
                        ->scalarNode('stream_factory')->defaultValue('httplug.stream_factory.default')->end()
                    ->end()
                ->end()
                ->arrayNode('classes')
                    ->addDefaultsIfNotSet()
                    ->info('Overwrite a service class instead of using the discovery mechanism.')
                    ->children()
                        ->scalarNode('client')->defaultNull()->end()
                        ->scalarNode('message_factory')->defaultNull()->end()
                        ->scalarNode('uri_factory')->defaultNull()->end()
                        ->scalarNode('stream_factory')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('toolbar')
                    ->addDefaultsIfNotSet()
                    ->info('Extend the debug profiler with inforation about requests.')
                    ->children()
                        ->enumNode('enabled')
                            ->info('If "auto" (default), the toolbar is activated when kernel.debug is true. You can force the toolbar on and off by changing this option.')
                            ->values([true, false, 'auto'])
                            ->defaultValue('auto')
                        ->end()
                        ->scalarNode('formatter')->defaultNull()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    protected function configureClients(ArrayNodeDefinition $root)
    {
        $root->children()
            ->arrayNode('clients')
                ->useAttributeAsKey('name')
                ->prototype('array')
                ->children()
                    ->scalarNode('factory')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->info('The service id of a factory to use when creating the adapter.')
                    ->end()
                    ->arrayNode('plugins')
                        ->info('A list of service ids of plugins. The order is important.')
                        ->prototype('scalar')->end()
                    ->end()
                    ->variableNode('config')->end()
                ->end()
            ->end();
    }
}
