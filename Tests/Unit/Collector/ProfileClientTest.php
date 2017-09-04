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
use Http\Promise\RejectedPromise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class ProfileClientTest extends TestCase
{
    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var Stack
     */
    private $activeStack;

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
     * @var StopwatchEvent
     */
    private $stopwatchEvent;

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
    private $fulfilledPromise;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var RejectedPromise
     */
    private $rejectedPromise;

    /**
     * @var UriInterface
     */
    private $uri;

    public function setUp()
    {
        $this->collector = $this->getMockBuilder(Collector::class)->disableOriginalConstructor()->getMock();
        $this->activeStack = new Stack('default', 'FormattedRequest');
        $this->client = $this->getMockBuilder(ClientInterface::class)->getMock();
        $this->uri = new Uri('https://example.com/target');
        $this->request = new Request('GET', $this->uri);
        $this->formatter = $this->getMockBuilder(Formatter::class)->disableOriginalConstructor()->getMock();
        $this->stopwatch = $this->getMockBuilder(Stopwatch::class)->disableOriginalConstructor()->getMock();
        $this->stopwatchEvent = $this->getMockBuilder(StopwatchEvent::class)->disableOriginalConstructor()->getMock();
        $this->subject = new ProfileClient($this->client, $this->collector, $this->formatter, $this->stopwatch);
        $this->response = new Response();
        $this->exception = new \Exception();
        $this->fulfilledPromise = new FulfilledPromise($this->response);
        $this->rejectedPromise = new RejectedPromise($this->exception);

        $this->collector->method('getActiveStack')->willReturn($this->activeStack);
        $this->formatter
            ->method('formatResponse')
            ->with($this->response)
            ->willReturn('FormattedResponse')
        ;
        $this->formatter
            ->method('formatException')
            ->with($this->exception)
            ->willReturn('FormattedException')
        ;

        $this->stopwatch
            ->method('start')
            ->willReturn($this->stopwatchEvent)
        ;

        $this->stopwatchEvent
            ->method('getDuration')
            ->willReturn(42)
        ;
    }

    public function testSendRequest()
    {
        $this->client
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->identicalTo($this->request))
            ->willReturn($this->response)
        ;

        $response = $this->subject->sendRequest($this->request);

        $this->assertEquals($this->response, $response);
        $this->assertEquals('GET', $this->activeStack->getRequestMethod());
        $this->assertEquals('/target', $this->activeStack->getRequestTarget());
        $this->assertEquals('example.com', $this->activeStack->getRequestHost());
        $this->assertEquals('https', $this->activeStack->getRequestScheme());
    }

    public function testSendAsyncRequest()
    {
        $this->client
            ->expects($this->once())
            ->method('sendAsyncRequest')
            ->with($this->identicalTo($this->request))
            ->willReturn($this->fulfilledPromise)
        ;

        $this->collector
            ->expects($this->once())
            ->method('deactivateStack')
        ;

        $promise = $this->subject->sendAsyncRequest($this->request);

        $this->assertEquals($this->fulfilledPromise, $promise);
        $this->assertEquals('GET', $this->activeStack->getRequestMethod());
        $this->assertEquals('/target', $this->activeStack->getRequestTarget());
        $this->assertEquals('example.com', $this->activeStack->getRequestHost());
        $this->assertEquals('https', $this->activeStack->getRequestScheme());
    }

    public function testOnFulfilled()
    {
        $this->collector
            ->expects($this->once())
            ->method('activateStack')
            ->with($this->activeStack)
        ;

        $this->stopwatchEvent
            ->expects($this->once())
            ->method('stop')
        ;

        $this->client
            ->method('sendAsyncRequest')
            ->willReturn($this->fulfilledPromise)
        ;

        $this->subject->sendAsyncRequest($this->request);

        $this->assertEquals(42, $this->activeStack->getDuration());
        $this->assertEquals(200, $this->activeStack->getResponseCode());
        $this->assertEquals('FormattedResponse', $this->activeStack->getClientResponse());
    }

    public function testOnRejected()
    {
        $this->collector
            ->expects($this->once())
            ->method('activateStack')
            ->with($this->activeStack)
        ;

        $this->stopwatchEvent
            ->expects($this->once())
            ->method('stop')
        ;

        $this->client
            ->method('sendAsyncRequest')
            ->willReturn($this->rejectedPromise)
        ;

        $this->subject->sendAsyncRequest($this->request);

        $this->assertEquals(42, $this->activeStack->getDuration());
        $this->assertEquals('FormattedException', $this->activeStack->getClientException());
    }
}

interface ClientInterface extends HttpClient, HttpAsyncClient
{
}
