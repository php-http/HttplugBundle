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
                        ->booleanNode('profile_discovered_client')->defaultTrue()->end()
                        ->booleanNode('profile_discovered_async_client')->defaultFalse()->end()
                        ->scalarNode('captured_body_length')
                            ->defaultValue(0)
                            ->canNotBeEmpty()
                            ->info('Limit long HTTP message bodies to x characters. If set to 0 we do not read the message body. Only available with the default formatter (FullHttpMessageFormatter).')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    protected function configureClients(ArrayNodeDefinition $root)
    {
        $root->children()
            ->arrayNode('clients')
                ->validate()
                    ->ifTrue(function ($clients) {
                        foreach ($clients as $name => $config) {
                            return $config['flexible_client'] && $config['http_methods_client'];
                        }

                        return false;
                    })
                    ->thenInvalid('A http client can\'t be decorated with both FlexibleHttpClient and HttpMethodsClient. Only one of the following options can be true. ("flexible_client", "http_methods_client")')->end()
                ->useAttributeAsKey('name')
                ->prototype('array')
                ->children()
                    ->scalarNode('factory')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->info('The service id of a factory to use when creating the adapter.')
                    ->end()
                    ->booleanNode('flexible_client')
                        ->defaultFalse()
                        ->info('Set to true to get the client wrapped in a FlexibleHttpClient which emulates async or sync behavior.')
                    ->end()
                    ->booleanNode('http_methods_client')
                        ->defaultFalse()
                        ->info('Set to true to get the client wrapped in a HttpMethodsClient which emulates provides functions for HTTP verbs.')
                    ->end()
                    ->enumNode('use_with_discovery')
                        ->values(['async_client', 'http_client', false])
                        ->defaultFalse()
                        ->info('If set to "http_client" this will be the client return when using dicovery to find a HTTP client.')
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
                    ->append($this->addAuthenticationPluiginNode())

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

    /**
     * Add configuration for authentication plugin.
     *
     * @return ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    private function addAuthenticationPluiginNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('authentication');
        $node
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->validate()
                    ->always()
                    ->then(function ($config) {
                        switch ($config['type']) {
                            case 'basic':
                                $this->validateAuthenticationType(['username', 'password'], $config, 'basic');
                                break;
                            case 'bearer':
                                $this->validateAuthenticationType(['token'], $config, 'bearer');
                                break;
                            case 'service':
                                $this->validateAuthenticationType(['service'], $config, 'service');
                                break;
                            case 'wsse':
                                $this->validateAuthenticationType(['username', 'password'], $config, 'wsse');
                                break;
                        }

                        return $config;
                    })
                ->end()
                ->children()
                    ->enumNode('type')
                        ->values(['basic', 'bearer', 'wsse', 'service'])
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('username')->end()
                    ->scalarNode('password')->end()
                    ->scalarNode('token')->end()
                    ->scalarNode('service')->end()
                    ->end()
                ->end()
            ->end(); // End authentication plugin

        return $node;
    }

    /**
     * Validate that the configuration fragment has the specified keys and none other.
     *
     * @param array  $expected Fields that must exist
     * @param array  $actual   Actual configuration hashmap
     * @param string $authName Name of authentication method for error messages
     *
     * @throws InvalidConfigurationException If $actual does not have exactly the keys specified in $expected (plus 'type')
     */
    private function validateAuthenticationType(array $expected, array $actual, $authName)
    {
        unset($actual['type']);
        $actual = array_keys($actual);
        sort($actual);
        sort($expected);

        if ($expected === $actual) {
            return;
        }

        throw new InvalidConfigurationException(sprintf(
            'Authentication "%s" requires %s but got %s',
            $authName,
            implode(', ', $expected),
            implode(', ', $actual)
        ));
    }
}
