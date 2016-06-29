<?php

namespace Http\HttplugBundle\DependencyInjection\Compiler;

use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\StreamFactoryDiscovery;
use Http\Discovery\UriFactoryDiscovery;
use Http\Message\MessageFactory;
use Http\Message\StreamFactory;
use Http\Message\UriFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds fallback and discovery services.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class DiscoveryPass implements CompilerPassInterface
{
    /**
     * Fallback services and classes.
     *
     * @var array
     */
    private $services = [
        'client' => HttpClient::class,
        'message_factory' => MessageFactory::class,
        'uri_factory' => UriFactory::class,
        'stream_factory' => StreamFactory::class,
    ];

    /**
     * Factories by type.
     *
     * @var array
     */
    private $factoryClasses = [
        'client' => HttpClientDiscovery::class,
        'message_factory' => MessageFactoryDiscovery::class,
        'uri_factory' => UriFactoryDiscovery::class,
        'stream_factory' => StreamFactoryDiscovery::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($this->services as $service => $class) {
            $serviceId = sprintf('httplug.%s.default', $service);

            if (false === $container->has($serviceId)) {
                // Register and create factory for service
                $definition = $container->register($serviceId, $class);
                $definition->setFactory([$this->factoryClasses[$service], 'find']);
                $definition->addArgument($class);
            }
        }
    }
}
