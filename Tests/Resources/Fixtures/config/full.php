<?php

$container->loadFromExtension('httplug', [
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
    'toolbar' => [
        'enabled' => true,
        'formatter' => 'my_toolbar_formatter',
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
            'my_brearer' => [
                'type' => 'bearer',
                'token' => 'foo',
            ],
            'my_service' => [
                'type' => 'service',
                'service' => 'my_auth_serivce',
            ],
        ],
        'cache' => [
            'stream_factory' => 'my_other_stream_factory',
            'config' => [
                'default_ttl' => 42,
                'respect_cache_headers' => false,
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
