<?php

namespace Http\HttplugBundle\Tests\Unit\Collector;

use Http\Client\HttpClient;
use Http\HttplugBundle\ClientFactory\ClientFactory;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\ProfileClient;
use Http\HttplugBundle\Collector\ProfileClientFactory;
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

    public function setUp()
    {
        $this->collector = $this->getMockBuilder(Collector::class)->disableOriginalConstructor()->getMock();
        $this->formatter = $this->getMockBuilder(Formatter::class)->disableOriginalConstructor()->getMock();
        $this->stopwatch = $this->getMockBuilder(Stopwatch::class)->getMock();
        $this->client = $this->getMockBuilder(HttpClient::class)->getMock();
    }

    public function testCreateClientFromClientFactory()
    {
        $factory = $this->getMockBuilder(ClientFactory::class)->getMock();
        $factory->method('createClient')->willReturn($this->client);

        $subject = new ProfileClientFactory($factory, $this->collector, $this->formatter, $this->stopwatch);

        $this->assertInstanceOf(ProfileClient::class, $subject->createClient());
    }

    public function testCreateClientFromCallable()
    {
        $factory = function ($config) {
            return $this->client;
        };

        $subject = new ProfileClientFactory($factory, $this->collector, $this->formatter, $this->stopwatch);

        $this->assertInstanceOf(ProfileClient::class, $subject->createClient());
    }
}
