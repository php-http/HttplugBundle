<?php

namespace Http\HttplugBundle;

use Http\Client\HttpClient;
use Http\Discovery\Exception\StrategyUnavailableException;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;

/**
 * A strategy that provide clients configured with HTTPlug bundle.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ConfiguredClientsStrategy implements DiscoveryStrategy
{
    /**
     * @var HttpClient
     */
    private static $client;

    /**
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        static::$client = $httpClient;

        HttpClientDiscovery::prependStrategy(self::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getCandidates($type)
    {
        if (static::$client !== null && $type == 'Http\Client\HttpClient') {
            return [['class' => function() { return static::$client; }]];
        }

        return [];
    }
}
