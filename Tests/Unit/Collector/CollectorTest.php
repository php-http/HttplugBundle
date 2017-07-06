<?php

namespace Http\HttplugBundle\Tests\Unit\Collector;

use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Stack;

class CollectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCollectClientNames()
    {
        $collector = new Collector();

        $collector->addStack(new Stack('default', 'GET / HTTP/1.1'));
        $collector->addStack(new Stack('acme', 'GET / HTTP/1.1'));
        $collector->addStack(new Stack('acme', 'GET / HTTP/1.1'));

        $this->assertEquals(['default', 'acme'], $collector->getClients());
    }
}
