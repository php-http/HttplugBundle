<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\Collector;

use Http\Client\HttpClient;
use Http\HttplugBundle\ClientFactory\ClientFactory;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\ProfileClient;
use Http\HttplugBundle\Collector\ProfileClientFactory;
use Http\Message\Formatter as MessageFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;

class ProfileClientFactoryTest extends TestCase
{
    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var HttpClient
     */
    private $client;

    public function setUp(): void
    {
        $this->collector = new Collector();
        $this->formatter = new Formatter($this->createMock(MessageFormatter::class), $this->createMock(MessageFormatter::class));
        $this->stopwatch = $this->createMock(Stopwatch::class);
        $this->client = $this->createMock(HttpClient::class);
    }

    public function testCreateClientFromClientFactory(): void
    {
        $factory = $this->getMockBuilder(ClientFactory::class)->getMock();
        $factory->method('createClient')->willReturn($this->client);

        $subject = new ProfileClientFactory($factory, $this->collector, $this->formatter, $this->stopwatch);

        $this->assertInstanceOf(ProfileClient::class, $subject->createClient());
    }

    public function testCreateClientFromCallable(): void
    {
        $factory = function ($config) {
            return $this->client;
        };

        $subject = new ProfileClientFactory($factory, $this->collector, $this->formatter, $this->stopwatch);

        $this->assertInstanceOf(ProfileClient::class, $subject->createClient());
    }
}
