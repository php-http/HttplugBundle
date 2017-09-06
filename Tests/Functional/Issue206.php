<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Functional;

use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\PluginClientFactory;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Http\HttplugBundle\Collector\PluginClientFactory as DebugFactory;

class Issue206 extends WebTestCase
{
    public function testCustomClientStillAvailable()
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();
        PluginClientFactory::setFactory([$container->get('Http\Client\Common\PluginClientFactory'), 'createClient']);

        // Create a client
        $myCustomClient = new HttpMethodsClient(HttpClientDiscovery::find(), MessageFactoryDiscovery::find());
        $pluginClient = (new PluginClientFactory())->createClient($myCustomClient, []);
    }
}
