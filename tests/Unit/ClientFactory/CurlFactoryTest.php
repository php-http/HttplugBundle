<?php

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\HttplugBundle\ClientFactory\CurlFactory;
use Http\Client\Curl\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CurlFactoryTest extends TestCase
{
    public function testCreateClient()
    {
        if (!class_exists(Client::class)) {
            $this->markTestSkipped('Curl client is not installed');
        }

        $factory = new CurlFactory(
            $this->getMockBuilder(ResponseFactoryInterface::class)->getMock(),
            $this->getMockBuilder(StreamFactoryInterface::class)->getMock()
        );
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);
    }
}
