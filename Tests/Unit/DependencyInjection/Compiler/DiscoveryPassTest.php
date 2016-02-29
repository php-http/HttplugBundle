<?php

namespace Http\HttplugBundle\Tests\Unit\DependencyInjection\Compiler;

use Http\HttplugBundle\DependencyInjection\Compiler\DiscoveryPass;
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
        $this->setDefinition('puli.discovery', new Definition('Puli\Discovery\Api\Discovery'));

        $this->compile();

        $this->assertContainerBuilderHasService('httplug.factory', 'Http\HttplugBundle\Util\HttplugFactory');

        $this->assertContainerBuilderHasService('httplug.client.default', 'Http\Client\HttpClient');
        $this->assertContainerBuilderHasService('httplug.message_factory.default', 'Http\Message\MessageFactory');
        $this->assertContainerBuilderHasService('httplug.uri_factory.default', 'Http\Message\UriFactory');
        $this->assertContainerBuilderHasService('httplug.stream_factory.default', 'Http\Message\StreamFactory');
    }

    public function testDiscoverypartialFallbacks()
    {
        $this->setDefinition('puli.discovery', new Definition('Puli\Discovery\Api\Discovery'));
        $this->setDefinition('httplug.client.default', new Definition('Http\Adapter\Guzzle6\Client'));

        $this->compile();

        $this->assertContainerBuilderHasService('httplug.factory', 'Http\HttplugBundle\Util\HttplugFactory');

        $this->assertContainerBuilderHasService('httplug.client.default', 'Http\Adapter\Guzzle6\Client');
        $this->assertContainerBuilderHasService('httplug.message_factory.default', 'Http\Message\MessageFactory');
        $this->assertContainerBuilderHasService('httplug.uri_factory.default', 'Http\Message\UriFactory');
        $this->assertContainerBuilderHasService('httplug.stream_factory.default', 'Http\Message\StreamFactory');
    }

    /**
     * Overridden test as we have dependencies in this compiler pass.
     *
     * @test
     */
    public function compilation_should_not_fail_with_empty_container()
    {
    }
}
