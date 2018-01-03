<?php
/**
 * This file is part of the HttplugBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\HttplugBundle\ClientFactory\MockFactory;
use Http\Mock\Client;
use PHPUnit\Framework\TestCase;

/**
 * @author Gary PEGEOT <garypegeot@gmail.com>
 */
class MockFactoryTest extends TestCase
{
    public function testCreateClient()
    {
        $factory = new MockFactory();
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);

        $client = new Client();

        $factory->setClient($client);

        $this->assertEquals($client, $factory->createClient());
    }
}
