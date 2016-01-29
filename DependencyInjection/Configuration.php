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
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
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
        $this->configurePlugins($rootNode);

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
                    ->variableNode('config')->defaultValue([])->end()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $root
     */
    protected function configurePlugins(ArrayNodeDefinition $root)
    {
        $root->children()
            ->arrayNode('plugins')
                ->addDefaultsIfNotSet()
                ->children()

                    ->arrayNode('authentication')
                    ->canBeEnabled()
                        ->children()
                            ->scalarNode('authentication')
                                ->info('This must be a service id to a service implementing Http\Message\Authentication')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end() // End authentication plugin

                    ->arrayNode('cache')
                    ->canBeEnabled()
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('cache_pool')
                                ->info('This must be a service id to a service implementing Psr\Cache\CacheItemPoolInterface')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('stream_factory')
                                ->info('This must be a service id to a service implementing Http\Message\StreamFactory')
                                ->defaultValue('httplug.stream_factory')
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('config')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('default_ttl')->defaultNull()->end()
                                    ->scalarNode('respect_cache_headers')->defaultTrue()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end() // End cache plugin

                    ->arrayNode('cookie')
                    ->canBeEnabled()
                        ->children()
                            ->scalarNode('cookie_jar')
                                ->info('This must be a service id to a service implementing Http\Message\CookieJar')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end() // End cookie plugin

                    ->arrayNode('decoder')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('use_content_encoding')->defaultTrue()->end()
                        ->end()
                    ->end() // End decoder plugin

                    ->arrayNode('history')
                    ->canBeEnabled()
                        ->children()
                            ->scalarNode('journal')
                                ->info('This must be a service id to a service implementing Http\Client\Plugin\Journal')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end() // End history plugin

                    ->arrayNode('logger')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('logger')
                                ->info('This must be a service id to a service implementing Psr\Log\LoggerInterface')
                                ->defaultValue('logger')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('formatter')
                                ->info('This must be a service id to a service implementing Http\Message\Formatter')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end() // End logger plugin

                    ->arrayNode('redirect')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('preserve_header')->defaultTrue()->end()
                            ->scalarNode('use_default_for_multiple')->defaultTrue()->end()
                        ->end()
                    ->end() // End redirect plugin

                    ->arrayNode('retry')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('retry')->defaultValue(1)->end()
                        ->end()
                    ->end() // End retry plugin

                    ->arrayNode('stopwatch')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('stopwatch')
                                ->info('This must be a service id to a service extending Symfony\Component\Stopwatch\Stopwatch')
                                ->defaultValue('debug.stopwatch')
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end() // End stopwatch plugin

                ->end()
            ->end()
        ->end();
    }
}
