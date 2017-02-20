<?php

namespace Http\HttplugBundle\Tests\Unit\Collector;

use Exception;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\Stack;
use Http\HttplugBundle\Collector\StackPlugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class StackPluginTest extends \PHPUnit_Framework_TestCase
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
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var Exception
     */
    private $exception;

    /**
     * @var StackPlugin
     */
    private $subject;

    public function setUp()
    {
        $this->collector = $this->getMockBuilder(Collector::class)->disableOriginalConstructor()->getMock();
        $this->formatter = $this->getMockBuilder(Formatter::class)->disableOriginalConstructor()->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)->getMock();
        $this->response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $this->exception = $this->getMockBuilder(Exception::class)->disableOriginalConstructor()->getMock();

        $this->formatter
            ->method('formatRequest')
            ->with($this->request)
            ->willReturn('FormattedRequest')
        ;

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

        $this->subject = new StackPlugin($this->collector, $this->formatter, 'default');
    }

    public function testStackIsInitialized()
    {
        $this->collector
            ->expects($this->once())
            ->method('addStack')
            ->with($this->callback(function (Stack $stack) {
                $this->assertEquals('default', $stack->getClient());
                $this->assertEquals('FormattedRequest', $stack->getRequest());

                return true;
            }))
        ;

        $next = function () {
            return $this->getMockBuilder(Promise::class)->getMock();
        };

        $this->subject->handleRequest($this->request, $next, function () {
        });
    }

    public function testOnFulfilled()
    {
        //Capture the current stack
        $currentStack = null;
        $this->collector
            ->method('addStack')
            ->with($this->callback(function (Stack $stack) use (&$currentStack) {
                $currentStack = $stack;

                return true;
            }))
        ;

        $next = function () {
            $promise = $this->getMockBuilder(Promise::class)->getMock();
            $promise->method('then')
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

            return $promise;
        };

        $promise = $this->subject->handleRequest($this->request, $next, function () {
        });

        $this->assertEquals($this->response, $promise->wait());
        $this->assertInstanceOf(Stack::class, $currentStack);
        $this->assertEquals('FormattedResponse', $currentStack->getResponse());
    }

    public function testOnRejected()
    {
        //Capture the current stack
        $currentStack = null;
        $this->collector
            ->method('addStack')
            ->with($this->callback(function (Stack $stack) use (&$currentStack) {
                $currentStack = $stack;

                return true;
            }))
        ;

        $this->setExpectedException(Exception::class);

        $next = function () {
            $promise = $this->getMockBuilder(Promise::class)->getMock();
            $promise
                ->method('then')
                ->will($this->returnCallback(function (callable $onFulfilled, callable $onRejected) {
                    $rejected = $this->getMockBuilder(Promise::class)->getMock();
                    $rejected
                        ->method('wait')
                        ->with(true)
                        ->willReturn($onRejected($this->exception));

                    return $rejected;
                }));

            return $promise;
        };

        $promise = $this->subject->handleRequest($this->request, $next, function () {
        });

        $this->assertEquals($this->exception, $promise->wait());
        $this->assertInstanceOf(Stack::class, $currentStack);
        $this->assertEquals('FormattedException', $currentStack->getResponse());
        $this->assertTrue($currentStack->isFailed());
    }
}
