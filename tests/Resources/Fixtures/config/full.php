<?php

$container->loadFromExtension('httplug', [
    'default_client_autowiring' => false,
    'main_alias' => [
        'client'          => 'my_client',
        'message_factory' => 'my_message_factory',
        'uri_factory'     => 'my_uri_factory',
        'stream_factory'  => 'my_stream_factory',
    ],
    'classes' => [
        'client'          => 'Http\Adapter\Guzzle6\Client',
        'message_factory' => 'Http\Message\MessageFactory\GuzzleMessageFactory',
        'uri_factory'     => 'Http\Message\UriFactory\GuzzleUriFactory',
        'stream_factory'  => 'Http\Message\StreamFactory\GuzzleStreamFactory',
    ],
    'clients' => [
        'test' => [
            'factory' => 'httplug.factory.guzzle6',
            'http_methods_client' => true,
            'plugins' => [
                'httplug.plugin.redirect',
                [
                    'add_host' => [
                        'host' => 'http://localhost',
                    ],
                ],
                [
                    'add_path' => [
                        'path' => '/api/v1',
                    ],
                ],
                [
                    'base_uri' => [
                        'uri' => 'http://localhost',
                    ],
                ],
                [
                    'content_type' => [
                        'skip_detection' => true,
                    ],
                ],
                [
                    'header_set' => [
                        'headers' => [
                            'X-FOO' => 'bar',
                        ],
                    ],
                ],
                [
                    'header_remove' => [
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
                ]
            ],
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
            'cookie_jar' => 'my_cookie_jar',
        ],
        'decoder' => [
            'enabled' => false,
        ],
        'history' => [
            'journal' => 'my_journal',
        ],
        'logger' => [
            'enabled' => false,
        ],
        'redirect' => [
            'enabled' => false,
        ],
        'retry' => [
            'enabled' => false,
        ],
        'stopwatch' => [
            'enabled' => false,
        ],
    ],
]);
