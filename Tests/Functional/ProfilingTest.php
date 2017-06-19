<?php

namespace Http\HttplugBundle\Tests\Functional;

use GuzzleHttp\Psr7\Request;
use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\StreamFactoryDiscovery;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\ProfileClient;
use Http\HttplugBundle\Collector\ProfilePlugin;
use Http\HttplugBundle\Collector\StackPlugin;
use Http\Message\Formatter\CurlCommandFormatter;
use Http\Message\Formatter\FullHttpMessageFormatter;
use Http\Mock\Client;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Stopwatch\Stopwatch;

class ProfilingTest extends \PHPUnit_Framework_TestCase
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

    public function setUp()
    {
        $this->collector = new Collector([]);
        $this->formatter = new Formatter(new FullHttpMessageFormatter(), new CurlCommandFormatter());
        $this->stopwatch = new Stopwatch();
    }

    public function testCachePluginProfiling()
    {
        $pool = new ArrayAdapter();

        $client = $this->createClient([
            new Plugin\CachePlugin($pool, StreamFactoryDiscovery::find(), [
                'respect_response_cache_directives' => [],
                'default_ttl' => 86400,
            ]),
        ]);

        $client->sendRequest(new Request('GET', 'https://example.com'));
        $client->sendRequest(new Request('GET', 'https://example.com'));

        $this->assertCount(2, $this->collector->getStacks());
        $stack = $this->collector->getStacks()[1];
        $this->assertEquals('GET', $stack->getRequestMethod());
        $this->assertEquals('https', $stack->getRequestScheme());
        $this->assertEquals('/', $stack->getRequestTarget());
        $this->assertEquals('example.com', $stack->getRequestHost());
    }

    private function createClient(array $plugins, $clientName = 'Acme', array $clientOptions = [])
    {
        $plugins = array_map(function (Plugin $plugin) {
            return new ProfilePlugin($plugin, $this->collector, $this->formatter, get_class($plugin));
        }, $plugins);

        array_unshift($plugins, new StackPlugin($this->collector, $this->formatter, $clientName));

        $client = new Client();
        $client = new ProfileClient($client, $this->collector, $this->formatter, $this->stopwatch);
        $client = new PluginClient($client, $plugins, $clientOptions);

        return $client;
    }
}
