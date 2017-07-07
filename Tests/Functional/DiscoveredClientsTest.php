<?php

namespace Http\HttplugBundle\Tests\Functional;

use Http\Client\Common\PluginClient;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\HttplugBundle\Collector\StackPlugin;
use Nyholm\NSA;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DiscoveredClientsTest extends WebTestCase
{
    public function testDiscoveredClient()
    {
        $container = $this->getContainer(false);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_client'));

        $service = $container->get('httplug.auto_discovery.auto_discovered_client');

        $this->assertInstanceOf(PluginClient::class, $service);
        $this->assertInstanceOf(HttpClient::class, NSA::getProperty($service, 'client'));
        $this->assertEmpty(NSA::getProperty($service, 'plugins'));
    }

    public function testDiscoveredAsyncClient()
    {
        $container = $this->getContainer(false);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_async'));

        $service = $container->get('httplug.auto_discovery.auto_discovered_async');

        $this->assertInstanceOf(PluginClient::class, $service);
        $this->assertInstanceOf(HttpAsyncClient::class, NSA::getProperty($service, 'client'));
        $this->assertEmpty(NSA::getProperty($service, 'plugins'));
    }

    public function testDiscoveredClientWithProfilingEnabled()
    {
        $container = $this->getContainer(true);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_client'));

        $service = $container->get('httplug.auto_discovery.auto_discovered_client');

        $this->assertInstanceOf(PluginClient::class, $service);
        $this->assertInstanceOf(HttpClient::class, NSA::getProperty($service, 'client'));

        $plugins = NSA::getProperty($service, 'plugins');
        $this->assertCount(1, $plugins);
        $this->assertInstanceOf(StackPlugin::class, $plugins[0]);
        $this->assertEquals('auto_discovered_client', NSA::getProperty($plugins[0], 'client'));
    }

    public function testDiscoveredAsyncClientWithProfilingEnabled()
    {
        $container = $this->getContainer(true);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_async'));

        $service = $container->get('httplug.auto_discovery.auto_discovered_async');

        $this->assertInstanceOf(PluginClient::class, $service);
        $this->assertInstanceOf(HttpAsyncClient::class, NSA::getProperty($service, 'client'));

        $plugins = NSA::getProperty($service, 'plugins');
        $this->assertCount(1, $plugins);
        $this->assertInstanceOf(StackPlugin::class, $plugins[0]);
        $this->assertEquals('auto_discovered_async', NSA::getProperty($plugins[0], 'client'));
    }

    private function getContainer($debug)
    {
        static::bootKernel(['debug' => $debug]);

        return static::$kernel->getContainer();
    }
}
