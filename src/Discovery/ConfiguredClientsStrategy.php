<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Discovery;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Symfony\Component\EventDispatcher\Event as LegacyEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Contracts\EventDispatcher\Event;

if (Kernel::MAJOR_VERSION >= 5) {
    \class_alias(Event::class, 'Http\HttplugBundle\Discovery\ConfiguredClientsStrategyEventClass');
} else {
    \class_alias(LegacyEvent::class, 'Http\HttplugBundle\Discovery\ConfiguredClientsStrategyEventClass');
}

/**
 * A strategy that provide clients configured with HTTPlug bundle. With help from this strategy
 * we can use the web debug toolbar for clients found with the discovery.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ConfiguredClientsStrategy implements DiscoveryStrategy, EventSubscriberInterface
{
    /**
     * @var HttpClient
     */
    private static $client;

    /**
     * @var HttpAsyncClient
     */
    private static $asyncClient;

    /**
     * @param HttpClient      $httpClient
     * @param HttpAsyncClient $asyncClient
     */
    public function __construct(HttpClient $httpClient = null, HttpAsyncClient $asyncClient = null)
    {
        self::$client = $httpClient;
        self::$asyncClient = $asyncClient;
        HttpClientDiscovery::clearCache();
    }

    /**
     * {@inheritdoc}
     */
    public static function getCandidates($type)
    {
        if (HttpClient::class === $type && null !== self::$client) {
            return [['class' => function () {
                return self::$client;
            }]];
        }

        if (HttpAsyncClient::class === $type && null !== self::$asyncClient) {
            return [['class' => function () {
                return self::$asyncClient;
            }]];
        }

        return [];
    }

    /**
     * Make sure to use our custom strategy.
     */
    public function onEvent(ConfiguredClientsStrategyEventClass $e)
    {
        HttpClientDiscovery::prependStrategy(self::class);
    }

    /**
     * Whenever these events occur we make sure to add our strategy to the discovery.
     *
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => ['onEvent', 1024],
            'console.command' => ['onEvent', 1024],
        ];
    }
}
