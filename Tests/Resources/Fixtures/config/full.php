<?php

$container->loadFromExtension('httplug', array(
    'main_alias' => array(
        'client' => 'my_client',
        'message_factory' => 'my_message_factory',
        'uri_factory' => 'my_uri_factory',
        'stream_factory' => 'my_stream_factory',
    ),
    'classes' => array(
        'client' => 'Http\Adapter\Guzzle6\Client',
        'message_factory' => 'Http\Message\MessageFactory\GuzzleMessageFactory',
        'uri_factory' => 'Http\Message\UriFactory\GuzzleUriFactory',
        'stream_factory' => 'Http\Message\StreamFactory\GuzzleStreamFactory',
    ),
));
