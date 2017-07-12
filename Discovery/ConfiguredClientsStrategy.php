<?php

namespace Http\HttplugBundle\Discovery;

use Http\Client\HttpClient;
use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A strategy that provide clients configured with HTTPlug bundle. With help from this strategy
 * we can use the web debug toolbar for clients found with the discovery.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ConfiguredClientsStrategy implements DiscoveryStrategy, EventSubscriberInterface
{
    /**
     * @var ServiceLocator|ContainerInterface
     */
    private static $locator;

    /**
     * @var string
     */
    private static $client;

    /**
     * @var string
     */
    private static $asyncClient;

    /**
     * @param ServiceLocator|ContainerInterface $locator
     * @param string                            $client
     * @param string                            $asyncClient
     */
    public function __construct($locator, $client, $asyncClient)
    {
        static::$locator = $locator;
        static::$client = $client;
        static::$asyncClient = $asyncClient;
    }

    /**
     * {@inheritdoc}
     */
    public static function getCandidates($type)
    {
        $locator = static::$locator;
        if ($type === HttpClient::class && $locator->has(static::$client)) {
            return [['class' => function () use ($locator) {
                return $locator->get(static::$client);
            }]];
        }

        if ($type === HttpAsyncClient::class && $locator->has(static::$asyncClient)) {
            return [['class' => function () use ($locator) {
                return $locator->get(static::$asyncClient);
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
            'kernel.request' => ['onEvent', 1024],
            'console.command' => ['onEvent', 1024],
        ];
    }
}
