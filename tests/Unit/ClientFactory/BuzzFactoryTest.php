<?php

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\Adapter\Buzz\Client;
use Http\HttplugBundle\ClientFactory\BuzzFactory;
use Http\Message\MessageFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class BuzzFactoryTest extends TestCase
{
    public function testCreateClient()
    {
        if (!class_exists(\Http\Adapter\Buzz\Client::class)) {
            $this->markTestSkipped('Buzz adapter is not installed');
        }

        $factory = new BuzzFactory($this->getMockBuilder(MessageFactory::class)->getMock());
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);
    }
}
