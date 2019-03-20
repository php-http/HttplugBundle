<?php

namespace Http\HttplugBundle\Tests\Unit\DependencyInjection\Compiler;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\HttplugBundle\DependencyInjection\HttplugExtension;
use Http\Message\MessageFactory;
use Http\Message\StreamFactory;
use Http\Message\UriFactory;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\ContainerBuilderHasAliasConstraint;
use PHPUnit\Framework\Constraint\LogicalNot;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class DiscoveryTest extends AbstractExtensionTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->setParameter('kernel.debug', true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerExtensions()
    {
        return [
            new HttplugExtension(),
        ];
    }

    public function testDiscoveryFallbacks()
    {
        $this->load();

        $this->assertContainerBuilderHasService('httplug.client.default', HttpClient::class);
        $this->assertContainerBuilderHasService('httplug.message_factory.default', MessageFactory::class);
        $this->assertContainerBuilderHasService('httplug.uri_factory.default', UriFactory::class);
        $this->assertContainerBuilderHasService('httplug.stream_factory.default', StreamFactory::class);
        $this->assertContainerBuilderHasService('httplug.async_client.default', HttpAsyncClient::class);
    }

    public function testDiscoveryPartialFallbacks()
    {
        $this->load();
        $this->setDefinition('httplug.client.default', new Definition('Http\Adapter\Guzzle6\Client'));

        $this->assertContainerBuilderHasService('httplug.client.default', 'Http\Adapter\Guzzle6\Client');
        $this->assertContainerBuilderHasService('httplug.message_factory.default', MessageFactory::class);
        $this->assertContainerBuilderHasService('httplug.uri_factory.default', UriFactory::class);
        $this->assertContainerBuilderHasService('httplug.stream_factory.default', StreamFactory::class);
        $this->assertContainerBuilderHasService('httplug.async_client.default', HttpAsyncClient::class);
    }

    public function testNoDiscoveryFallbacks()
    {
        $this->setDefinition('httplug.client.default', new Definition(HttpClient::class));
        $this->setDefinition('httplug.message_factory.default', new Definition(MessageFactory::class));
        $this->setDefinition('httplug.uri_factory.default', new Definition(UriFactory::class));
        $this->setDefinition('httplug.stream_factory.default', new Definition(StreamFactory::class));
        $this->setDefinition('httplug.async_client.default', new Definition(HttpAsyncClient::class));

        $this->load();

        $this->assertContainerBuilderHasService('httplug.client.default', HttpClient::class);
        $clientDefinition = $this->container->getDefinition('httplug.client.default');
        $this->assertEquals([HttpClientDiscovery::class, 'find'], $clientDefinition->getFactory());
    }

    public function testEnableAutowiring()
    {
        $this->load([
            'default_client_autowiring' => true,
        ]);

        $this->assertContainerBuilderHasService('httplug.client.default');
        $this->assertContainerBuilderHasService('httplug.async_client.default');
        $this->assertContainerBuilderHasAlias(HttpClient::class);
        $this->assertContainerBuilderHasAlias(HttpAsyncClient::class);
    }

    public function testDisableAutowiring()
    {
        if (PHP_VERSION_ID <= 70000) {
            $this->markTestSkipped();
        }

        $this->load([
            'default_client_autowiring' => false,
        ]);

        $this->assertContainerBuilderHasService('httplug.client.default');
        $this->assertContainerBuilderHasService('httplug.async_client.default');

        self::assertThat(
            $this->container,
            new LogicalNot(new ContainerBuilderHasAliasConstraint(HttpClient::class))
        );
        self::assertThat(
            $this->container,
            new LogicalNot(new ContainerBuilderHasAliasConstraint(HttpAsyncClient::class))
        );
    }
}
