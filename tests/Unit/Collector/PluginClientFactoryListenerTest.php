<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\Collector;

use Http\Client\Common\PluginClientFactory as DefaultPluginClientFactory;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\PluginClientFactory;
use Http\HttplugBundle\Collector\PluginClientFactoryListener;
use Nyholm\NSA;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\Event;

final class PluginClientFactoryListenerTest extends TestCase
{
    public function testRegisterPluginClientFactory(): void
    {
        $collector = $this->getMockBuilder(Collector::class)->getMock();
        $formatter = $this->getMockBuilder(Formatter::class)->disableOriginalConstructor()->getMock();
        $stopwatch = $this->getMockBuilder(Stopwatch::class)->getMock();

        $factory = new PluginClientFactory($collector, $formatter, $stopwatch);

        $listener = new PluginClientFactoryListener($factory);

        $listener->onEvent(new Event());

        $this->assertTrue(is_callable(NSA::getProperty(DefaultPluginClientFactory::class, 'factory')));
    }
}
