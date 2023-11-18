<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\Collector;

use Http\Client\Common\PluginClientFactory as DefaultPluginClientFactory;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\PluginClientFactory;
use Http\HttplugBundle\Collector\PluginClientFactoryListener;
use Http\Message\Formatter as MessageFormatter;
use Nyholm\NSA;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Event as LegacyEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\Event;

final class PluginClientFactoryListenerTest extends TestCase
{
    public function testRegisterPluginClientFactory(): void
    {
        $collector = new Collector();
        $formatter = new Formatter($this->createMock(MessageFormatter::class), $this->createMock(MessageFormatter::class));
        $stopwatch = $this->createMock(Stopwatch::class);

        $factory = new PluginClientFactory($collector, $formatter, $stopwatch);

        $listener = new PluginClientFactoryListener($factory);

        $class = (Kernel::MAJOR_VERSION >= 5) ? Event::class : LegacyEvent::class;
        $listener->onEvent(new $class());

        $this->assertIsCallable(NSA::getProperty(DefaultPluginClientFactory::class, 'factory'));
    }
}
