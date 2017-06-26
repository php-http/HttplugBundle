<?php

namespace Http\HttplugBundle\Tests\Unit\Collector;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\ProfileClient;
use Http\HttplugBundle\Collector\Stack;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class ProfileClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var Stack
     */
    private $currentStack;

    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var ProfileClient
     */
    private $subject;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var Promise
     */
    private $promise;

    /**
     * @var UriInterface
     */
    private $uri;

    public function setUp()
    {
        $this->collector = $this->getMockBuilder(Collector::class)->disableOriginalConstructor()->getMock();
        $this->currentStack = new Stack('default', 'FormattedRequest');
        $this->client = $this->getMockBuilder(ClientInterface::class)->getMock();
        $this->uri = new Uri('https://example.com/target');
        $this->request = new Request('GET', $this->uri);
        $this->formatter = $this->getMockBuilder(Formatter::class)->disableOriginalConstructor()->getMock();
        $this->stopwatch = new Stopwatch();
        $this->subject = new ProfileClient($this->client, $this->collector, $this->formatter, $this->stopwatch);
        $this->response = new Response();
        $this->promise = new FulfilledPromise($this->response);

        $this->client->method('sendRequest')->willReturn($this->response);
        $this->client->method('sendAsyncRequest')->will($this->returnCallback(function () {
            return $this->promise;
        }));

        $this->collector->method('getCurrentStack')->willReturn($this->currentStack);
    }

    public function testCallDecoratedClient()
    {
        $this->client
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->identicalTo($this->request))
        ;

        $this->client
            ->expects($this->once())
            ->method('sendAsyncRequest')
            ->with($this->identicalTo($this->request))
        ;

        $this->assertEquals($this->response, $this->subject->sendRequest($this->request));
        $this->assertEquals($this->promise, $this->subject->sendAsyncRequest($this->request));
    }

    public function testCollectRequestInformations()
    {
        $this->subject->sendRequest($this->request);

        $this->assertEquals('GET', $this->currentStack->getRequestMethod());
        $this->assertEquals('/target', $this->currentStack->getRequestTarget());
        $this->assertEquals('example.com', $this->currentStack->getRequestHost());
        $this->assertEquals('https', $this->currentStack->getRequestScheme());
    }

    public function testCollectAsyncRequestInformations()
    {
        $this->subject->sendAsyncRequest($this->request);

        $this->assertEquals('GET', $this->currentStack->getRequestMethod());
        $this->assertEquals('/target', $this->currentStack->getRequestTarget());
        $this->assertEquals('example.com', $this->currentStack->getRequestHost());
        $this->assertEquals('https', $this->currentStack->getRequestScheme());
    }
}

interface ClientInterface extends HttpClient, HttpAsyncClient
{
}
