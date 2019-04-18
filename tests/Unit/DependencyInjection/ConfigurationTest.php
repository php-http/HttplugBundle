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
        'default_client_autowiring' => true,
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
                    'methods' => ['GET', 'HEAD'],
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
            'default_client_autowiring' => false,
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
                    'service' => null,
                    'public' => null,
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
                            'add_path' => [
                                'enabled' => true,
                                'path' => '/api/v1',
                            ],
                        ],
                        [
                            'base_uri' => [
                                'enabled' => true,
                                'uri' => 'http://localhost',
                                'replace' => false,
                            ],
                        ],
                        [
                            'content_type' => [
                                'enabled' => true,
                                'skip_detection' => true,
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
                                    'params' => [],
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
                        'params' => [],
                    ],
                    'my_wsse' => [
                        'type' => 'wsse',
                        'username' => 'foo',
                        'password' => 'bar',
                        'params' => [],
                    ],
                    'my_bearer' => [
                        'type' => 'bearer',
                        'token' => 'foo',
                        'params' => [],
                    ],
                    'my_service' => [
                        'type' => 'service',
                        'service' => 'my_auth_service',
                        'params' => [],
                    ],
                ],
                'cache' => [
                    'enabled' => true,
                    'cache_pool' => 'my_cache_pool',
                    'stream_factory' => 'my_other_stream_factory',
                    'config' => [
                        'cache_lifetime' => 2592000,
                        'default_ttl' => 42,
                        'hash_algo' => 'sha1',
                        'methods' => ['GET'],
                        'cache_key_generator' => null,
                        'respect_response_cache_directives' => ['X-Foo'],
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
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "httplug.plugins.cache.config": You can't provide config option "respect_cache_headers" and "respect_response_cache_directives" simultaniously. Use "respect_response_cache_directives" instead.
     */
    public function testInvalidCacheConfig()
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/invalid_cache_config.yml';
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    /**
     * @group legacy
     */
    public function testBackwardCompatibility()
    {
        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/bc/toolbar.yml',
            'config/bc/toolbar_auto.yml',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($this->emptyConfig, [$format]);
        }
    }

    /**
     * @group legacy
     */
    public function testCacheConfigDeprecationCompatibility()
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/bc/cache_config.yml';
        $config = $this->emptyConfig;
        $config['plugins']['cache'] = array_merge($config['plugins']['cache'], [
            'enabled' => true,
            'cache_pool' => 'my_cache_pool',
            'config' => [
                'methods' => ['GET', 'HEAD'],
                'respect_cache_headers' => true,
            ],
        ]);
        $this->assertProcessedConfigurationEquals($config, [$file]);
    }

    /**
     * @group legacy
     */
    public function testCacheConfigDeprecationCompatibilityIssue166()
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/bc/issue-166.yml';
        $config = $this->emptyConfig;
        $config['plugins']['cache'] = array_merge($config['plugins']['cache'], [
            'enabled' => true,
            'cache_pool' => 'my_cache_pool',
            'config' => [
                'methods' => ['GET', 'HEAD'],
                'respect_cache_headers' => false,
            ],
        ]);
        $this->assertProcessedConfigurationEquals($config, [$file]);
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

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The child node "cache_pool" at path "httplug.clients.test.plugins.0.cache" must be configured.
     */
    public function testClientCacheConfigMustHavePool()
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/client_cache_config_with_no_pool.yml';
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The child node "cache_pool" at path "httplug.plugins.cache" must be configured.
     */
    public function testCacheConfigMustHavePool()
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/cache_config_with_no_pool.yml';
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    public function testLimitlessCapturedBodyLength()
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/limitless_captured_body_length.yml';
        $config = $this->emptyConfig;
        $config['profiling']['captured_body_length'] = null;
        $this->assertProcessedConfigurationEquals($config, [$file]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The child node "captured_body_length" at path "httplug.profiling" must be an integer or null
     */
    public function testInvalidCapturedBodyLengthString()
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/invalid_captured_body_length.yml';
        $this->assertProcessedConfigurationEquals([], [$file]);
    }
}
