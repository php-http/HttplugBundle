<?php

namespace Http\HttplugBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ServiceInstantiationTest extends WebTestCase
{
    public static function setUpBeforeClass()
    {
        static::bootKernel();
    }

    public function testHttpClient()
    {
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has('httplug.client'));
        $client = $container->get('httplug.client');
        $this->assertInstanceOf('Http\Client\HttpClient', $client);
    }
}
