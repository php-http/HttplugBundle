<?php

namespace Http\HttplugBundle\Tests\Unit\DependencyInjection;

use Http\HttplugBundle\DependencyInjection\Configuration;
use Http\HttplugBundle\DependencyInjection\HttplugExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;

/**
 * @author David Buchmann <mail@davidbu.ch>
 */
class ConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    private $emptyConfig = [
        'main_alias' => [
            'client' => 'httplug.client.default',
            'message_factory' => 'httplug.message_factory.default',
            'uri_factory' => 'httplug.uri_factory.default',
            'stream_factory' => 'httplug.stream_factory.default',
        ],
        'classes' => [
            'client' => null,
            'message_factory' => null,
            'uri_factory' => null,
            'stream_factory' => null,
        ],
        'clients' => [],
        'profiling' => [
            'enabled' => true,
            'formatter' => null,
            'captured_body_length' => 0,
        ],
        'plugins' => [
            'authentication' => [],
            'cache' => [
                'enabled' => false,
                'stream_factory' => 'httplug.stream_factory',
                'config' => [
                    'default_ttl' => 0,
                    'respect_cache_headers' => null,
                    'respect_response_cache_directives' => null,
                ],
            ],
            'cookie' => [
                'enabled' => false,
            ],
            'decoder' => [
                'enabled' => true,
                'use_content_encoding' => true,
            ],
            'history' => [
                'enabled' => false,
            ],
            'logger' => [
                'enabled' => true,
                'logger' => 'logger',
                'formatter' => null,
            ],
            'redirect' => [
                'enabled' => true,
                'preserve_header' => true,
                'use_default_for_multiple' => true,
            ],
            'retry' => [
                'enabled' => true,
                'retry' => 1,
            ],
            'stopwatch' => [
                'enabled' => true,
                'stopwatch' => 'debug.stopwatch',
            ],
        ],
        'discovery' => [
            'client' => 'auto',
            'async_client' => null,
        ],
    ];

    protected function getContainerExtension()
    {
        return new HttplugExtension();
    }

    protected function getConfiguration()
    {
        return new Configuration(true);
    }

    public function testEmptyConfiguration()
    {
        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/empty.yml',
            'config/empty.xml',
            'config/empty.php',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($this->emptyConfig, [$format]);
        }
    }

    public function testSupportsAllConfigFormats()
    {
        $expectedConfiguration = [
            'main_alias' => [
                'client' => 'my_client',
                'message_factory' => 'my_message_factory',
                'uri_factory' => 'my_uri_factory',
                'stream_factory' => 'my_stream_factory',
            ],
            'classes' => [
                'client' => 'Http\Adapter\Guzzle6\Client',
                'message_factory' => 'Http\Message\MessageFactory\GuzzleMessageFactory',
                'uri_factory' => 'Http\Message\UriFactory\GuzzleUriFactory',
                'stream_factory' => 'Http\Message\StreamFactory\GuzzleStreamFactory',
            ],
            'clients' => [
                'test' => [
                    'factory' => 'httplug.factory.guzzle6',
                    'http_methods_client' => true,
                    'flexible_client' => false,
                    'batch_client' => false,
                    'plugins' => [
                        [
                            'reference' => [
                                'enabled' => true,
                                'id' => 'httplug.plugin.redirect',
                            ],
                        ],
                        [
                            'add_host' => [
                                'enabled' => true,
                                'host' => 'http://localhost',
                                'replace' => false,
                            ],
                        ],
                        [
                            'header_set' => [
                                'enabled' => true,
                                'headers' => [
                                    'X-FOO' => 'bar',
                                ],
                            ],
                        ],
                        [
                            'header_remove' => [
                                'enabled' => true,
                                'headers' => [
                                    'X-FOO',
                                ],
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
                    'config' => [],
                ],
            ],
            'profiling' => [
                'enabled' => true,
                'formatter' => 'my_toolbar_formatter',
                'captured_body_length' => 0,
            ],
            'plugins' => [
                'authentication' => [
                    'my_basic' => [
                        'type' => 'basic',
                        'username' => 'foo',
                        'password' => 'bar',
                    ],
                    'my_wsse' => [
                        'type' => 'wsse',
                        'username' => 'foo',
                        'password' => 'bar',
                    ],
                    'my_bearer' => [
                        'type' => 'bearer',
                        'token' => 'foo',
                    ],
                    'my_service' => [
                        'type' => 'service',
                        'service' => 'my_auth_service',
                    ],
                ],
                'cache' => [
                    'enabled' => true,
                    'cache_pool' => 'my_cache_pool',
                    'stream_factory' => 'my_other_stream_factory',
                    'config' => [
                        'default_ttl' => 42,
                        'respect_cache_headers' => false,
                        'respect_response_cache_directives' => ['X-Foo', 'X-Bar'],
                    ],
                ],
                'cookie' => [
                    'enabled' => true,
                    'cookie_jar' => 'my_cookie_jar',
                ],
                'decoder' => [
                    'enabled' => false,
                    'use_content_encoding' => true,
                ],
                'history' => [
                    'enabled' => true,
                    'journal' => 'my_journal',
                ],
                'logger' => [
                    'enabled' => false,
                    'logger' => 'logger',
                    'formatter' => null,
                ],
                'redirect' => [
                    'enabled' => false,
                    'preserve_header' => true,
                    'use_default_for_multiple' => true,
                ],
                'retry' => [
                    'enabled' => false,
                    'retry' => 1,
                ],
                'stopwatch' => [
                    'enabled' => false,
                    'stopwatch' => 'debug.stopwatch',
                ],
            ],
            'discovery' => [
                'client' => 'auto',
                'async_client' => null,
            ],
        ];

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/full.yml',
            'config/full.xml',
            'config/full.php',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, [$format]);
        }
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Nonexisting\Class
     */
    public function testMissingClass()
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/invalid_class.yml';
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unrecognized option "foobar" under "httplug.clients.acme.plugins.0"
     */
    public function testInvalidPlugin()
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/invalid_plugin.yml';
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage password, service, username
     */
    public function testInvalidAuthentication()
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/invalid_auth.yml';
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    /**
     * @group legacy
     */
    public function testBackwardCompatibility()
    {
        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures//'.$path;
        }, [
            'config/bc/toolbar.yml',
            'config/bc/toolbar_auto.yml',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($this->emptyConfig, [$format]);
        }
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Can't configure both "toolbar" and "profiling" section. The "toolbar" config is deprecated as of version 1.3.0, please only use "profiling".
     */
    public function testProfilingToolbarCollision()
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/bc/profiling_toolbar.yml';
        $this->assertProcessedConfigurationEquals([], [$file]);
    }
}
