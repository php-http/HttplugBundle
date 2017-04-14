<?php

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\Client\Socket\Client;
use Http\HttplugBundle\ClientFactory\SocketFactory;
use Http\Message\MessageFactory;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SocketFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateClient()
    {
        $factory = new SocketFactory($this->getMockBuilder(MessageFactory::class)->getMock());
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);
    }
}
