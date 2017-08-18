<?php

namespace Http\HttplugBundle\Collector;

use Http\Client\Common\PluginClientFactory;
use Http\HttplugBundle\Collector\PluginClientFactory as CollectorPluginClientFactory;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
