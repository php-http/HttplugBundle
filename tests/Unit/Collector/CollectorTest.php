<?php

namespace Http\HttplugBundle\Tests\Unit\Collector;

use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Stack;
use PHPUnit\Framework\TestCase;

class CollectorTest extends TestCase
{
    public function testCollectClientNames()
    {
        $collector = new Collector();

        $collector->addStack(new Stack('default', 'GET / HTTP/1.1'));
        $collector->addStack(new Stack('acme', 'GET / HTTP/1.1'));
        $collector->addStack(new Stack('acme', 'GET / HTTP/1.1'));

        $this->assertEquals(['default', 'acme'], $collector->getClients());
    }

    public function testActivateStack()
    {
        $parent = new Stack('acme', 'GET / HTTP/1.1');
        $stack = new Stack('acme', 'GET / HTTP/1.1');

        $collector = new Collector();

        $collector->activateStack($parent);
        $collector->activateStack($stack);

        $this->assertEquals($parent, $stack->getParent());
        $this->assertEquals($stack, $collector->getActiveStack());
    }

    public function testDeactivateStack()
    {
        $stack = new Stack('acme', 'GET / HTTP/1.1');
        $collector = new Collector();

        $collector->activateStack($stack);
        $this->assertNotNull($collector->getActiveStack());

        $collector->deactivateStack($stack);
        $this->assertNull($collector->getActiveStack());
    }

    public function testDeactivateStackSetParentAsActiveStack()
    {
        $parent = new Stack('acme', 'GET / HTTP/1.1');
        $stack = new Stack('acme', 'GET / HTTP/1.1');

        $collector = new Collector();

        $collector->activateStack($parent);
        $collector->activateStack($stack);
        $collector->deactivateStack($stack);

        $this->assertEquals($parent, $collector->getActiveStack());
    }

    public function testAddStack()
    {
        $stack = new Stack('acme', 'GET / HTTP/1.1');
        $collector = new Collector();

        $collector->addStack($stack);

        $this->assertEquals(['acme'], $collector->getClients());
        $this->assertEquals([$stack], $collector->getClientRootStacks('acme'));
    }

    public function testResetAction()
    {
        $stack = new Stack('acme', 'GET / HTTP/1.1');

        $collector = new Collector();
        $collector->addStack($stack);
        $collector->activateStack($stack);

        $collector->reset();

        $this->assertNull($collector->getActiveStack());
        $this->assertEmpty($collector->getStacks());
    }
}
