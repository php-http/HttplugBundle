<?php

namespace Http\HttplugBundle\Discovery;

use Http\Client\HttpClient;
use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

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
        static::$client = $httpClient;
        static::$asyncClient = $asyncClient;
    }

    /**
     * {@inheritdoc}
     */
    public static function getCandidates($type)
    {
        if (static::$client !== null && $type == HttpClient::class) {
            return [['class' => function () {
                return static::$client;
            }]];
        }

        if (static::$asyncClient !== null && $type == HttpAsyncClient::class) {
            return [['class' => function () {
                return static::$asyncClient;
            }]];
        }

        return [];
    }

    /**
     * Make sure to use our custom strategy.
     *
     * @param Event $e
     */
    public function onEvent(Event $e)
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
            KernelEvents::REQUEST => ['onEvent', 1024],
            ConsoleEvents::COMMAND => ['onEvent', 1024],
        ];
    }
}
