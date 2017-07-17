<?php

namespace Http\HttplugBundle\Tests\Unit\DependencyInjection;

use Http\Client\Common\PluginClient;
use Http\HttplugBundle\DependencyInjection\HttplugExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Reference;

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
                            'authentication' => [
                                'my_basic' => [
                                    'type' => 'basic',
                                    'username' => 'foo',
                                    'password' => 'bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $plugins = [
            'httplug.client.acme.plugin.stack',
            'httplug.client.acme.plugin.decoder.debug',
            'httplug.plugin.redirect.debug',
            'httplug.client.acme.plugin.add_host.debug',
            'httplug.client.acme.plugin.header_append.debug',
            'httplug.client.acme.plugin.header_defaults.debug',
            'httplug.client.acme.plugin.header_set.debug',
            'httplug.client.acme.plugin.header_remove.debug',
            'httplug.client.acme.authentication.my_basic.debug',
        ];
        $pluginReferences = array_map(function ($id) {
            return new Reference($id);
        }, $plugins);

        $this->assertContainerBuilderHasService('httplug.client.acme');
        foreach ($plugins as $id) {
            $this->assertContainerBuilderHasService($id);
        }
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('httplug.client.acme', 0, $pluginReferences);
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

        $def = $this->container->findDefinition('httplug.client');
        $arguments = $def->getArguments();

        $this->assertTrue(isset($arguments[3]));
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

    private function verifyProfilingDisabled()
    {
        $def = $this->container->findDefinition('httplug.client');
        $arguments = $def->getArguments();

        if (isset($arguments[3])) {
            $this->assertEmpty(
                $arguments[3],
                'Parameter 3 to the PluginClient must not contain any debug_plugin information when profiling is disabled'
            );
        }
    }
}
