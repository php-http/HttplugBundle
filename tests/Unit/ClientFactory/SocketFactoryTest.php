<?php

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\Client\Socket\Client;
use Http\HttplugBundle\ClientFactory\SocketFactory;
use Http\Message\MessageFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SocketFactoryTest extends TestCase
{
    public function testCreateClient()
    {
        if (!class_exists(\Http\Client\Socket\Client::class)) {
            $this->markTestSkipped('Socket client is not installed');
        }

        $factory = new SocketFactory($this->getMockBuilder(MessageFactory::class)->getMock());
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);
    }
}
