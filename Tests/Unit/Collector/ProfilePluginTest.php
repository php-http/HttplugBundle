<?php

namespace Http\HttplugBundle\Tests\Unit\Collector;

use Exception;
use Http\Client\Common\Plugin;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\ProfilePlugin;
use Http\HttplugBundle\Collector\Stack;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ProfilePluginTest extends \PHPUnit_Framework_TestCase
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
     * @var Stack
     */
    private $currentStack;

    /**
     * @var Promise
     */
    private $promise;

    /**
     * @var Exception
     */
    private $exception;

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
        $this->request = $this->getMockBuilder(RequestInterface::class)->getMock();
        $this->response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $this->currentStack = new Stack('default', 'FormattedRequest');
        $this->promise = $this->getMockBuilder(Promise::class)->getMock();
        $this->exception = $this->getMockBuilder(Exception::class)->disableOriginalConstructor()->getMock();
        $this->formatter = $this->getMockBuilder(Formatter::class)->disableOriginalConstructor()->getMock();

        $this->collector
            ->method('getCurrentStack')
            ->willReturn($this->currentStack)
        ;

        $this->plugin
            ->method('handleRequest')
            ->willReturnCallback(function ($request, $next, $first) {
                $next($request);

                return $this->promise;
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
        }, function () {
        });
    }

    public function testProfileIsInitialized()
    {
        $this->subject->handleRequest($this->request, function () {
        }, function () {
        });

        $this->assertCount(1, $this->currentStack->getProfiles());
        $profile = $this->currentStack->getProfiles()[0];
        $this->assertEquals('http.plugin.mock', $profile->getPlugin());
    }

    public function testCollectRequestInformations()
    {
        $this->subject->handleRequest($this->request, function () {
        }, function () {
        });

        $profile = $this->currentStack->getProfiles()[0];
        $this->assertEquals('FormattedRequest', $profile->getRequest());
    }

    public function testOnFulfilled()
    {
        $this->promise
            ->method('then')
            ->will($this->returnCallback(function (callable $onFulfilled) {
                $fulfilled = $this->getMockBuilder(Promise::class)->getMock();
                $fulfilled
                    ->method('wait')
                    ->with(true)
                    ->willReturn($onFulfilled($this->response))
                ;

                return $fulfilled;
            }))
        ;

        $promise = $this->subject->handleRequest($this->request, function () {
        }, function () {
        });

        $this->assertEquals($this->response, $promise->wait());
        $profile = $this->currentStack->getProfiles()[0];
        $this->assertEquals('FormattedResponse', $profile->getResponse());
    }

    public function testOnRejected()
    {
        $this->setExpectedException(Exception::class);

        $this->promise
            ->method('then')
            ->will($this->returnCallback(function (callable $onFulfilled, callable $onRejected) {
                $rejected = $this->getMockBuilder(Promise::class)->getMock();
                $rejected
                    ->method('wait')
                    ->with(true)
                    ->willReturn($onRejected($this->exception))
                ;

                return $rejected;
            }))
        ;

        $promise = $this->subject->handleRequest($this->request, function () {
        }, function () {
        });

        $this->assertEquals($this->exception, $promise->wait());
        $profile = $this->currentStack->getProfiles()[0];
        $this->assertEquals('FormattedException', $profile->getResponse());
    }
}
