<?php

namespace Http\HttplugBundle\Collector;

use Http\Client\Common\PluginClientFactory;
use Http\HttplugBundle\Collector\PluginClientFactory as CollectorPluginClientFactory;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber ensures that every PluginClient created when using Http\Client\Common\PluginClientFactory without
 * using the Symfony dependency injection container uses the Http\HttplugBundle\Collector\PluginClientFactory factory
 * when profiling is enabled. This allows 0 config profiling of third party libraries which use HTTPlug.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
final class PluginClientFactorySubscriber implements EventSubscriberInterface
{
    /**
     * @var CollectorPluginClientFactory
     */
    private $factory;

    /**
     * @param CollectorPluginClientFactory $factory
     */
    public function __construct(CollectorPluginClientFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Make sure to profile clients created using PluginClientFactory.
     *
     * @param Event $e
     */
    public function onEvent(Event $e)
    {
        PluginClientFactory::setFactory([$this->factory, 'createClient']);
    }

    /**
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
