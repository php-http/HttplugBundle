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

    /**
     * Test with httplug.discovery.client: "auto"
     */
    public function testDiscovery()
    {
        $container = $this->getContainer(true);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_client'));
        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_async'));
        $this->assertTrue($container->has('httplug.strategy'));

        $container->get('httplug.strategy');

        $httpClient = $container->get('httplug.auto_discovery.auto_discovered_client');
        $httpAsyncClient = $container->get('httplug.auto_discovery.auto_discovered_async');
        $this->assertInstanceOf(PluginClient::class, $httpClient);
        $this->assertInstanceOf(PluginClient::class, $httpAsyncClient);
    }

    /**
     * Test with httplug.discovery.client: null
     */
    public function testDisabledDiscovery()
    {
        $container = $this->getContainer(true, 'discovery_disabled');

        $this->assertFalse($container->has('httplug.auto_discovery.auto_discovered_client'));
        $this->assertFalse($container->has('httplug.auto_discovery.auto_discovered_async'));
        $this->assertFalse($container->has('httplug.strategy'));
    }

    /**
     * Test with httplug.discovery.client: "httplug.client.acme"
     */
    public function testForcedDiscovery()
    {
        $container = $this->getContainer(true, 'discovery_forced');

        $this->assertFalse($container->has('httplug.auto_discovery.auto_discovered_client'));
        $this->assertFalse($container->has('httplug.auto_discovery.auto_discovered_async'));
        $this->assertTrue($container->has('httplug.strategy'));

        $strategy = $container->get('httplug.strategy');

        $this->assertEquals($container->get('httplug.client.acme'), NSA::getProperty($strategy, 'client'));
        $this->assertEquals($container->get('httplug.client.acme'), NSA::getProperty($strategy, 'asyncClient'));
    }

    private function getContainer($debug, $environment = 'test')
    {
        static::bootKernel(['debug' => $debug, 'environment' => $environment]);

        return static::$kernel->getContainer();
    }

    protected function setUp()
    {
        parent::setUp();

        // Reset values
        new ConfiguredClientsStrategy(null, null, null);
    }


}
