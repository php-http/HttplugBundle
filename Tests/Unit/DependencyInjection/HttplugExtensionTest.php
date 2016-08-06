<?php

namespace Http\HttplugBundle\Tests\Unit\DependencyInjection;

use Http\Client\Common\PluginClient;
use Http\HttplugBundle\DependencyInjection\HttplugExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

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

    public function testClientExtraPlugins()
    {
        $this->load([
            'clients' => [
                'acme' => [
                    'factory' => 'httplug.factory.curl',
                    'extra_plugins' => [
                        'add_host' => [
                            'host' => 'http://localhost:8000',
                        ],
                        'decoder' => [
                            'use_content_encoding' => false,
                        ]
                    ],
                    'plugins' => ['httplug.client.acme.plugin.decoder', 'httplug.plugin.redirect'],
                ],
            ],
        ]);

        $plugins = [
            'httplug.client.acme.plugin.decoder',
            'httplug.plugin.redirect',
            'httplug.client.acme.plugin.add_host',
        ];

        $this->assertContainerBuilderHasService('httplug.client.acme');
        foreach ($plugins as $id) {
            $this->assertContainerBuilderHasService($id);
        }
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('httplug.client.acme', 0, $plugins);
    }

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
        $this->assertTrue(isset($arguments[3]['debug_plugins']));
        $this->assertFalse(empty($arguments[3]['debug_plugins']));
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
