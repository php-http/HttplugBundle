<?php

/*
 * This file is part of the Http Client bundle.
 *
 * (c) David Buchmann <mail@davidbu.ch>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Http\ClientBundle\Tests\Unit\DependencyInjection;

use FOS\HttpCacheBundle\DependencyInjection\FOSHttpCacheExtension;
use Http\ClientBundle\DependencyInjection\HttpClientExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class HttpClientExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return array(
            new HttpClientExtension(),
        );
    }

    public function testConfigLoadDefault()
    {
        $this->load();

        foreach (['client', 'message_factory', 'uri_factory'] as $type) {
            $this->assertContainerBuilderHasAlias("http_client.$type", "http_client.$type.default");
        }

        $this->assertContainerBuilderHasService('http_client.client.default', 'Http\Adapter\HttpAdapter');
        $this->assertContainerBuilderHasService('http_client.message_factory.default', 'Http\Message\MessageFactory');
        $this->assertContainerBuilderHasService('http_client.uri_factory.default', 'Http\Message\UriFactory');
    }

    public function testConfigLoadClass()
    {
        $this->load(array(
            'classes' => array(
                'client' => 'Http\Adapter\Guzzle6HttpAdapter'
            ),
        ));

        foreach (['client', 'message_factory', 'uri_factory'] as $type) {
            $this->assertContainerBuilderHasAlias("http_client.$type", "http_client.$type.default");
        }

        $this->assertContainerBuilderHasService('http_client.client.default', 'Http\Adapter\Guzzle6HttpAdapter');
        $this->assertContainerBuilderHasService('http_client.message_factory.default', 'Http\Message\MessageFactory');
        $this->assertContainerBuilderHasService('http_client.uri_factory.default', 'Http\Message\UriFactory');
    }

    public function testConfigLoadService()
    {
        $this->load(array(
            'main_alias' => array(
                'client' => 'my_client_service',
                'message_factory' => 'my_message_factory_service',
                'uri_factory' => 'my_uri_factory_service',
            ),
        ));

        foreach (['client', 'message_factory', 'uri_factory'] as $type) {
            $this->assertContainerBuilderHasAlias("http_client.$type", "my_{$type}_service");
        }

        $this->assertContainerBuilderHasService('http_client.client.default', 'Http\Adapter\HttpAdapter');
        $this->assertContainerBuilderHasService('http_client.message_factory.default', 'Http\Message\MessageFactory');
        $this->assertContainerBuilderHasService('http_client.uri_factory.default', 'Http\Message\UriFactory');
    }
}
