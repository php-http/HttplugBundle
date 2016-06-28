<?php

namespace Http\HttplugBundle\Tests\Unit\DependencyInjection\Compiler;

use Http\Client\HttpClient;
use Http\HttplugBundle\DependencyInjection\Compiler\DiscoveryPass;
use Http\HttplugBundle\HttplugFactory;
use Http\Message\MessageFactory;
use Http\Message\StreamFactory;
use Http\Message\UriFactory;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class DiscoveryPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DiscoveryPass());
    }

    public function testDiscoveryFallbacks()
    {
        $this->compile();

        $this->assertContainerBuilderHasService('httplug.client.default', HttpClient::class);
        $this->assertContainerBuilderHasService('httplug.message_factory.default', MessageFactory::class);
        $this->assertContainerBuilderHasService('httplug.uri_factory.default', UriFactory::class);
        $this->assertContainerBuilderHasService('httplug.stream_factory.default', StreamFactory::class);
    }

    public function testDiscoveryPartialFallbacks()
    {
        $this->setDefinition('httplug.client.default', new Definition('Http\Adapter\Guzzle6\Client'));

        $this->compile();

        $this->assertContainerBuilderHasService('httplug.client.default', 'Http\Adapter\Guzzle6\Client');
        $this->assertContainerBuilderHasService('httplug.message_factory.default', MessageFactory::class);
        $this->assertContainerBuilderHasService('httplug.uri_factory.default', UriFactory::class);
        $this->assertContainerBuilderHasService('httplug.stream_factory.default', StreamFactory::class);
    }

    public function testNoDiscoveryFallbacks()
    {
        $this->setDefinition('httplug.client.default', new Definition(HttpClient::class));
        $this->setDefinition('httplug.message_factory.default', new Definition(MessageFactory::class));
        $this->setDefinition('httplug.uri_factory.default', new Definition(UriFactory::class));
        $this->setDefinition('httplug.stream_factory.default', new Definition(StreamFactory::class));

        $this->compile();
    }
}
