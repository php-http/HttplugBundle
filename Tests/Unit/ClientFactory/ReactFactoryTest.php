<?php

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\Adapter\React\Client;
use Http\HttplugBundle\ClientFactory\ReactFactory;
use Http\Message\MessageFactory;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ReactFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateClient()
    {
        $factory = new ReactFactory($this->getMockBuilder(MessageFactory::class)->getMock());
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);
    }
}
