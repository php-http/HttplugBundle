<?php

namespace Http\HttplugBundle\Tests\Functional;

use Http\Client\HttpClient;
use Http\HttplugBundle\Collector\Collector;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class ServiceInstantiationTest extends WebTestCase
{
    public function testHttpClient()
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has('httplug.client'));
        $client = $container->get('httplug.client');
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testHttpClientNoDebug()
    {
        static::bootKernel(['debug' => false]);
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has('httplug.client'));
        $client = $container->get('httplug.client');
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testDebugToolbar()
    {
        static::bootKernel(['debug' => true]);
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has('profiler'));
        $profiler = $container->get('profiler');
        $this->assertInstanceOf(Profiler::class, $profiler);
        $this->assertTrue($profiler->has('httplug'));
        $collector = $profiler->get('httplug');
        $this->assertInstanceOf(Collector::class, $collector);
    }
}
