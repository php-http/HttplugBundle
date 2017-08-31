<?php

namespace Http\HttplugBundle\Tests\Unit\Collector;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Common\Plugin;
use Http\Client\Exception\TransferException;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\ProfilePlugin;
use Http\HttplugBundle\Collector\Stack;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Http\Promise\RejectedPromise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ProfilePluginTest extends TestCase
{
    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var Promise
     */
    private $fulfilledPromise;

    /**
     * @var Stack
     */
    private $currentStack;

    /**
     * @var TransferException
     */
    private $exception;

    /**
     * @var Promise
     */
    private $rejectedPromise;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var ProfilePlugin
     */
    private $subject;

    public function setUp()
    {
        $this->plugin = $this->getMockBuilder(Plugin::class)->getMock();
        $this->collector = $this->getMockBuilder(Collector::class)->disableOriginalConstructor()->getMock();
        $this->request = new Request('GET', '/');
        $this->response = new Response();
        $this->fulfilledPromise = new FulfilledPromise($this->response);
        $this->currentStack = new Stack('default', 'FormattedRequest');
        $this->exception = new TransferException();
        $this->rejectedPromise = new RejectedPromise($this->exception);
        $this->formatter = $this->getMockBuilder(Formatter::class)->disableOriginalConstructor()->getMock();

        $this->collector
            ->method('getActiveStack')
            ->willReturn($this->currentStack)
        ;

        $this->plugin
            ->method('handleRequest')
            ->willReturnCallback(function ($request, $next, $first) {
                return $next($request);
            })
        ;

        $this->formatter
            ->method('formatRequest')
            ->with($this->identicalTo($this->request))
            ->willReturn('FormattedRequest')
        ;

        $this->formatter
            ->method('formatResponse')
            ->with($this->identicalTo($this->response))
            ->willReturn('FormattedResponse')
        ;

        $this->formatter
            ->method('formatException')
            ->with($this->identicalTo($this->exception))
            ->willReturn('FormattedException')
        ;

        $this->subject = new ProfilePlugin(
            $this->plugin,
            $this->collector,
            $this->formatter,
            'http.plugin.mock'
        );
    }

    public function testCallDecoratedPlugin()
    {
        $this->plugin
            ->expects($this->once())
            ->method('handleRequest')
            ->with($this->request)
        ;

        $this->subject->handleRequest($this->request, function () {
            return $this->fulfilledPromise;
        }, function () {
        });
    }

    public function testProfileIsInitialized()
    {
        $this->subject->handleRequest($this->request, function () {
            return $this->fulfilledPromise;
        }, function () {
        });

        $this->assertCount(1, $this->currentStack->getProfiles());
        $profile = $this->currentStack->getProfiles()[0];
        $this->assertEquals(get_class($this->plugin), $profile->getPlugin());
    }

    public function testCollectRequestInformations()
    {
        $this->subject->handleRequest($this->request, function () {
            return $this->fulfilledPromise;
        }, function () {
        });

        $profile = $this->currentStack->getProfiles()[0];
        $this->assertEquals('FormattedRequest', $profile->getRequest());
    }

    public function testOnFulfilled()
    {
        $promise = $this->subject->handleRequest($this->request, function () {
            return $this->fulfilledPromise;
        }, function () {
        });

        $this->assertEquals($this->response, $promise->wait());
        $profile = $this->currentStack->getProfiles()[0];
        $this->assertEquals('FormattedResponse', $profile->getResponse());
    }

    /**
     * @expectedException \Http\Client\Exception\TransferException
     */
    public function testOnRejected()
    {
        $promise = $this->subject->handleRequest($this->request, function () {
            return $this->rejectedPromise;
        }, function () {
        });

        $this->assertEquals($this->exception, $promise->wait());
        $profile = $this->currentStack->getProfiles()[0];
        $this->assertEquals('FormattedException', $profile->getResponse());
    }
}
