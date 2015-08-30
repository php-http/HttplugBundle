<?php

$container->loadFromExtension('httplug', array(
    'main_alias' => array(
        'client' => 'my_client',
        'message_factory' => 'my_message_factory',
        'uri_factory' => 'my_uri_factory',
    ),
    'classes' => array(
        'client' => 'Http\Adapter\Guzzle6HttpAdapter',
        'message_factory' => 'Http\Discovery\MessageFactory\GuzzleFactory',
        'uri_factory' => 'Http\Discovery\UriFactory\GuzzleFactory',
    ),
));
