<?php

namespace Http\HttplugBundle\Tests\Unit\DependencyInjection;

use Http\HttplugBundle\DependencyInjection\HttplugExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

/**
 * @author David Buchmann <mail@davidbu.ch>
 */
class HttplugExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return array(
            new HttplugExtension(),
        );
    }

    public function testConfigLoadDefault()
    {
        $this->load();

        foreach (['client', 'message_factory', 'uri_factory', 'stream_factory'] as $type) {
            $this->assertContainerBuilderHasAlias("httplug.$type", "httplug.$type.default");
        }

        $this->assertContainerBuilderHasService('httplug.client.default', 'Http\Client\HttpClient');
        $this->assertContainerBuilderHasService('httplug.message_factory.default', 'Http\Message\MessageFactory');
        $this->assertContainerBuilderHasService('httplug.uri_factory.default', 'Http\Message\UriFactory');
        $this->assertContainerBuilderHasService('httplug.stream_factory.default', 'Http\Message\StreamFactory');
    }

    public function testConfigLoadClass()
    {
        $this->load(array(
            'classes' => array(
                'client' => 'Http\Adapter\Guzzle6\Client'
            ),
        ));

        foreach (['client', 'message_factory', 'uri_factory', 'stream_factory'] as $type) {
            $this->assertContainerBuilderHasAlias("httplug.$type", "httplug.$type.default");
        }

        $this->assertContainerBuilderHasService('httplug.client.default', 'Http\Adapter\Guzzle6\Client');
        $this->assertContainerBuilderHasService('httplug.message_factory.default', 'Http\Message\MessageFactory');
        $this->assertContainerBuilderHasService('httplug.uri_factory.default', 'Http\Message\UriFactory');
        $this->assertContainerBuilderHasService('httplug.stream_factory.default', 'Http\Message\StreamFactory');
    }

    public function testConfigLoadService()
    {
        $this->load(array(
            'main_alias' => array(
                'client' => 'my_client_service',
                'message_factory' => 'my_message_factory_service',
                'uri_factory' => 'my_uri_factory_service',
                'stream_factory' => 'my_stream_factory_service',
            ),
        ));

        foreach (['client', 'message_factory', 'uri_factory', 'stream_factory'] as $type) {
            $this->assertContainerBuilderHasAlias("httplug.$type", "my_{$type}_service");
        }

        $this->assertContainerBuilderHasService('httplug.client.default', 'Http\Client\HttpClient');
        $this->assertContainerBuilderHasService('httplug.message_factory.default', 'Http\Message\MessageFactory');
        $this->assertContainerBuilderHasService('httplug.uri_factory.default', 'Http\Message\UriFactory');
        $this->assertContainerBuilderHasService('httplug.stream_factory.default', 'Http\Message\StreamFactory');
    }
}
