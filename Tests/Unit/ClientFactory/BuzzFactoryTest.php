<?php

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\Adapter\Buzz\Client;
use Http\HttplugBundle\ClientFactory\BuzzFactory;
use Http\Message\MessageFactory;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class BuzzFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateClient()
    {
        $factory = new BuzzFactory($this->getMock(MessageFactory::class));
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);
    }
}
