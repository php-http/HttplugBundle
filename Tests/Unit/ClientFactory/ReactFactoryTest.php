<?php

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\Adapter\React\Client;
use Http\HttplugBundle\ClientFactory\ReactFactory;
use Http\Message\MessageFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ReactFactoryTest extends TestCase
{
    public function testCreateClient()
    {
        if (!class_exists(\Http\Adapter\React\Client::class)) {
            $this->markTestSkipped('React adapter is not installed');
        }

        $factory = new ReactFactory($this->getMockBuilder(MessageFactory::class)->getMock());
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);
    }
}
