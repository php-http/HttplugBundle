<?php

namespace Http\HttplugBundle\Tests\Unit\DependencyInjection;

use Http\Client\HttpClient;
use Http\HttplugBundle\Collector\PluginClientFactoryListener;
use Http\HttplugBundle\DependencyInjection\HttplugExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author David Buchmann <mail@davidbu.ch>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class HttplugExtensionTest extends AbstractExtensionTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->setParameter('kernel.debug', true);
    }

    protected function getContainerExtensions()
    {
        return [
            new HttplugExtension(),
        ];
    }

    public function testConfigLoadDefault()
    {
        $this->load();

        foreach (['client', 'message_factory', 'uri_factory', 'stream_factory'] as $type) {
            $this->assertContainerBuilderHasAlias("httplug.$type", "httplug.$type.default");
        }
    }

    public function testConfigLoadClass()
    {
        $this->load([
            'classes' => [
                'client' => 'Http\Adapter\Guzzle6\Client',
            ],
        ]);

        $this->assertContainerBuilderHasService('httplug.client.default', 'Http\Adapter\Guzzle6\Client');
    }

    public function testConfigLoadService()
    {
        $this->load([
            'main_alias' => [
                'client' => 'my_client_service',
                'message_factory' => 'my_message_factory_service',
                'uri_factory' => 'my_uri_factory_service',
                'stream_factory' => 'my_stream_factory_service',
            ],
        ]);

        foreach (['client', 'message_factory', 'uri_factory', 'stream_factory'] as $type) {
            $this->assertContainerBuilderHasAlias("httplug.$type", "my_{$type}_service");
        }
    }

    public function testClientPlugins()
    {
        $this->load([
            'clients' => [
                'acme' => [
                    'factory' => 'httplug.factory.curl',
                    'plugins' => [
                        [
                            'decoder' => [
                                'use_content_encoding' => false,
                            ],
                        ],
                        'httplug.plugin.redirect',
                        [
                            'add_host' => [
                                'host' => 'http://localhost:8000',
                            ],
                        ],
                        [
                            'content_type' => [
                                'skip_detection' => true,
                            ],
                        ],
                        [
                            'header_append' => [
                                'headers' => ['X-FOO' => 'bar'],
                            ],
                        ],
                        [
                            'header_defaults' => [
                                'headers' => ['X-FOO' => 'bar'],
                            ],
                        ],
                        [
                            'header_set' => [
                                'headers' => ['X-FOO' => 'bar'],
                            ],
                        ],
                        [
                            'header_remove' => [
                                'headers' => ['X-FOO'],
                            ],
                        ],
                        [
                            'query_defaults' => [
                                'parameters' => ['locale' => 'en'],
                            ],
                        ],
                        [
                            'authentication' => [
                                'my_basic' => [
                                    'type' => 'basic',
                                    'username' => 'foo',
                                    'password' => 'bar',
                                ],
                            ],
                        ],
                        [
                            'cache' => [
                                'cache_pool' => 'my_cache_pool',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $plugins = [
            'httplug.client.acme.plugin.decoder',
            'httplug.plugin.redirect',
            'httplug.client.acme.plugin.add_host',
            'httplug.client.acme.plugin.content_type',
            'httplug.client.acme.plugin.header_append',
            'httplug.client.acme.plugin.header_defaults',
            'httplug.client.acme.plugin.header_set',
            'httplug.client.acme.plugin.header_remove',
            'httplug.client.acme.plugin.query_defaults',
            'httplug.client.acme.authentication.my_basic',
            'httplug.client.acme.plugin.cache',
        ];
        $pluginReferences = array_map(function ($id) {
            return new Reference($id);
        }, $plugins);

        $this->assertContainerBuilderHasService('httplug.client.acme');
        foreach ($plugins as $id) {
            $this->assertContainerBuilderHasService($id);
        }
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('httplug.client.acme', 1, $pluginReferences);
        $this->assertContainerBuilderHasService('httplug.client.mock');
    }

    /**
     * @group legacy
     */
    public function testNoProfilingWhenToolbarIsDisabled()
    {
        $this->load(
            [
                'toolbar' => [
                    'enabled' => false,
                ],
                'clients' => [
                    'acme' => [
                        'factory' => 'httplug.factory.curl',
                        'plugins' => ['foo'],
                    ],
                ],
            ]
        );

        $this->verifyProfilingDisabled();
    }

    public function testNoProfilingWhenNotInDebugMode()
    {
        $this->setParameter('kernel.debug', false);
        $this->load(
            [
                'clients' => [
                    'acme' => [
                        'factory' => 'httplug.factory.curl',
                        'plugins' => ['foo'],
                    ],
                ],
            ]
        );

        $this->verifyProfilingDisabled();
    }

    /**
     * @group legacy
     */
    public function testProfilingWhenToolbarIsSpecificallyOn()
    {
        $this->setParameter('kernel.debug', false);
        $this->load(
            [
                'toolbar' => [
                    'enabled' => true,
                ],
                'clients' => [
                    'acme' => [
                        'factory' => 'httplug.factory.curl',
                        'plugins' => ['foo'],
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasService(PluginClientFactoryListener::class);
    }

    public function testOverrideProfillingFormatter()
    {
        $this->load(
            [
                'profiling' => [
                    'formatter' => 'acme.formatter',
                ],
            ]
        );

        $def = $this->container->findDefinition('httplug.collector.formatter');
        $this->assertEquals('acme.formatter', (string) $def->getArgument(0));
    }

    public function testCachePluginConfigCacheKeyGeneratorReference()
    {
        $this->load([
            'plugins' => [
                'cache' => [
                    'cache_pool' => 'my_cache_pool',
                    'config' => [
                        'cache_key_generator' => 'header_cache_key_generator',
                    ],
                ],
            ],
        ]);

        $cachePlugin = $this->container->findDefinition('httplug.plugin.cache');

        $config = $cachePlugin->getArgument(2);
        $this->assertArrayHasKey('cache_key_generator', $config);
        $this->assertInstanceOf(Reference::class, $config['cache_key_generator']);
        $this->assertSame('header_cache_key_generator', (string) $config['cache_key_generator']);
    }

    public function testContentTypePluginAllowedOptions()
    {
        $this->load([
            'clients' => [
                'acme' => [
                    'plugins' => [
                        [
                            'content_type' => [
                                'skip_detection' => true,
                                'size_limit' => 200000,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $cachePlugin = $this->container->findDefinition('httplug.client.acme.plugin.content_type');

        $config = $cachePlugin->getArgument(0);
        $this->assertEquals([
            'skip_detection' => true,
            'size_limit' => 200000,
        ], $config);
    }

    public function testUsingServiceKeyForClients()
    {
        $this->load([
            'clients' => [
                'acme' => [
                    'service' => 'my_custom_client',
                ],
            ],
        ]);

        $client = $this->container->getAlias('httplug.client.acme.client');
        $this->assertEquals('my_custom_client', (string) $client);
        $this->assertFalse($client->isPublic());
    }

    private function verifyProfilingDisabled()
    {
        $def = $this->container->findDefinition('httplug.client');
        $this->assertTrue(is_subclass_of($def->getClass(), HttpClient::class));
        $arguments = $def->getArguments();

        if (isset($arguments[3])) {
            $this->assertEmpty(
                $arguments[3],
                'Parameter 3 to the PluginClient must not contain any debug_plugin information when profiling is disabled'
            );
        }
    }

    public function testClientShouldHaveDefaultVisibility()
    {
        $this->load([
            'clients' => [
                'acme' => [],
            ],
        ]);

        $this->assertContainerBuilderHasService('httplug.client.acme');

        if (version_compare(Kernel::VERSION, '3.4', '>=')) {
            // Symfony made services private by default starting from 3.4
            $this->assertTrue($this->container->getDefinition('httplug.client.acme')->isPublic());
            $this->assertTrue($this->container->getDefinition('httplug.client.acme')->isPrivate());
        } else {
            // Legacy Symfony
            $this->assertTrue($this->container->getDefinition('httplug.client.acme')->isPublic());
        }
    }

    public function testFlexibleClientShouldBePrivateByDefault()
    {
        $this->load([
            'clients' => [
                'acme' => [
                    'flexible_client' => true,
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('httplug.client.acme');
        $this->assertFalse($this->container->getDefinition('httplug.client.acme.flexible')->isPublic());
    }

    public function testHttpMethodsClientShouldBePrivateByDefault()
    {
        $this->load([
            'clients' => [
                'acme' => [
                    'http_methods_client' => true,
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('httplug.client.acme');
        $this->assertFalse($this->container->getDefinition('httplug.client.acme.http_methods')->isPublic());
    }

    public function testBatchClientShouldBePrivateByDefault()
    {
        $this->load([
            'clients' => [
                'acme' => [
                    'batch_client' => true,
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('httplug.client.acme');
        $this->assertFalse($this->container->getDefinition('httplug.client.acme.batch_client')->isPublic());
    }

    public function testClientCanBePublic()
    {
        $this->load([
            'clients' => [
                'acme' => [
                    'public' => true,
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('httplug.client.acme');
        $this->assertTrue($this->container->getDefinition('httplug.client.acme')->isPublic());

        if (version_compare(Kernel::VERSION, '3.4', '>=')) {
            // Symfony made services private by default starting from 3.4
            $this->assertFalse($this->container->getDefinition('httplug.client.acme')->isPrivate());
        }
    }

    public function testFlexibleClientCanBePublic()
    {
        $this->load([
            'clients' => [
                'acme' => [
                    'public' => true,
                    'flexible_client' => true,
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('httplug.client.acme');
        $this->assertTrue($this->container->getDefinition('httplug.client.acme.flexible')->isPublic());

        if (version_compare(Kernel::VERSION, '3.4', '>=')) {
            // Symfony made services private by default starting from 3.4
            $this->assertFalse($this->container->getDefinition('httplug.client.acme.flexible')->isPrivate());
        }
    }

    public function testHttpMethodsClientCanBePublic()
    {
        $this->load([
            'clients' => [
                'acme' => [
                    'public' => true,
                    'http_methods_client' => true,
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('httplug.client.acme');
        $this->assertTrue($this->container->getDefinition('httplug.client.acme.http_methods')->isPublic());

        if (version_compare(Kernel::VERSION, '3.4', '>=')) {
            // Symfony made services private by default starting from 3.4
            $this->assertFalse($this->container->getDefinition('httplug.client.acme.http_methods')->isPrivate());
        }
    }

    public function testBatchClientCanBePublic()
    {
        $this->load([
            'clients' => [
                'acme' => [
                    'public' => true,
                    'batch_client' => true,
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('httplug.client.acme');
        $this->assertTrue($this->container->getDefinition('httplug.client.acme.batch_client')->isPublic());

        if (version_compare(Kernel::VERSION, '3.4', '>=')) {
            // Symfony made services private by default starting from 3.4
            $this->assertFalse($this->container->getDefinition('httplug.client.acme.batch_client')->isPrivate());
        }
    }
}
