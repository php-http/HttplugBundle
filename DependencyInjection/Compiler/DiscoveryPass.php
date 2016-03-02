<?php

namespace Http\HttplugBundle\DependencyInjection\Compiler;

use Http\Client\HttpClient;
use Http\HttplugBundle\HttplugFactory;
use Http\Message\MessageFactory;
use Http\Message\StreamFactory;
use Http\Message\UriFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Reference;

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
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $useDiscovery = false;

        foreach ($this->services as $service => $class) {
            $serviceId = sprintf('httplug.%s.default', $service);

            if (false === $container->has($serviceId)) {
                // Register and create factory for the first time
                if (false === $useDiscovery) {
                    $this->registerFactory($container);

                    $factory = [
                        new Reference('httplug.factory'),
                        'find',
                    ];

                    $useDiscovery = true;
                }

                $definition = $container->register($serviceId, $class);

                $definition->setFactory($factory);
                $definition->addArgument($class);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @throws RuntimeException
     */
    private function registerFactory(ContainerBuilder $container)
    {
        if (false === $container->has('puli.discovery')) {
            throw new RuntimeException(
                'You need to install puli/symfony-bundle or add configuration at httplug.classes in order to use this bundle. Refer to http://docs.php-http.org/en/latest/integrations/symfony-bundle.html#discovery-of-factory-classes'
            );
        }

        $definition = $container->register('httplug.factory', HttplugFactory::class);

        $definition
            ->addArgument(new Reference('puli.discovery'))
            ->setPublic(false)
        ;
    }
}
