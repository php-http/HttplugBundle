<?php

namespace Http\HttplugBundle\Tests\Unit\Collector;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\ProfileClient;
use Http\HttplugBundle\Collector\Stack;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

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
        $this->request = $this->getMockBuilder(RequestInterface::class)->getMock();
        $this->formatter = $this->getMockBuilder(Formatter::class)->disableOriginalConstructor()->getMock();
        $this->subject = new ProfileClient($this->client, $this->collector, $this->formatter);
        $this->response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $this->promise = $this->getMockBuilder(Promise::class)->getMock();
        $this->uri = $this->getMockBuilder(UriInterface::class)->getMock();

        $this->client->method('sendRequest')->willReturn($this->response);
        $this->client->method('sendAsyncRequest')->will($this->returnCallback(function () {
            $promise = $this->getMockBuilder(Promise::class)->getMock();
            $promise->method('then')->willReturn($this->promise);

            return $promise;
        }));

        $this->request->method('getMethod')->willReturn('GET');
        $this->request->method('getRequestTarget')->willReturn('/target');
        $this->request->method('getUri')->willReturn($this->uri);

        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');

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
