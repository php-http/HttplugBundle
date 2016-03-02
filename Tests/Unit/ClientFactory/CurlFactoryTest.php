<?php

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\HttplugBundle\ClientFactory\CurlFactory;
use Http\Client\Curl\Client;
use Http\Message\MessageFactory;
use Http\Message\StreamFactory;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CurlFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateClient()
    {
        $factory = new CurlFactory($this->getMock(MessageFactory::class), $this->getMock(StreamFactory::class));
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);
    }
}
