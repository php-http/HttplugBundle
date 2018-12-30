<?php

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\HttplugBundle\ClientFactory\CurlFactory;
use Http\Client\Curl\Client;
use Http\Message\MessageFactory;
use Http\Message\StreamFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CurlFactoryTest extends TestCase
{
    public function testCreateClient()
    {
        if (!class_exists(\Http\Client\Curl\Client::class)) {
            $this->markTestSkipped('Curl client is not installed');
        }

        $factory = new CurlFactory(
            $this->getMockBuilder(MessageFactory::class)->getMock(),
            $this->getMockBuilder(StreamFactory::class)->getMock()
        );
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);
    }
}
