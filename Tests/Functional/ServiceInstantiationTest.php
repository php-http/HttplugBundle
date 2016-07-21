<?php

namespace Http\HttplugBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ServiceInstantiationTest extends WebTestCase
{
    public function testHttpClient()
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has('httplug.client'));
        $client = $container->get('httplug.client');
        $this->assertInstanceOf('Http\Client\HttpClient', $client);
    }

    public function testHttpClientNoDebug()
    {
        static::bootKernel(['debug' => false]);
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has('httplug.client'));
        $client = $container->get('httplug.client');
        $this->assertInstanceOf('Http\Client\HttpClient', $client);
    }
}
