<?php

namespace Http\HttplugBundle\Tests\Unit\Collector;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\HttpException;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\Stack;
use Http\HttplugBundle\Collector\StackPlugin;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class StackPluginTest extends TestCase
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
     * @var \Exception
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
        $this->request = new Request('GET', '/');
        $this->response = new Response();
        $this->exception = new HttpException('', $this->request, $this->response);

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
        $this->collector
            ->expects($this->once())
            ->method('activateStack')
        ;

        $next = function () {
            return new FulfilledPromise($this->response);
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
        $this->collector
            ->expects($this->once())
            ->method('deactivateStack')
        ;

        $next = function () {
            return new FulfilledPromise($this->response);
        };

        $promise = $this->subject->handleRequest($this->request, $next, function () {
        });

        $this->assertEquals($this->response, $promise->wait());
        $this->assertInstanceOf(Stack::class, $currentStack);
        $this->assertEquals('FormattedResponse', $currentStack->getResponse());
    }

    /**
     * @expectedException \Exception
     */
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
        $this->collector
            ->expects($this->once())
            ->method('deactivateStack')
        ;

        $next = function () {
            return new RejectedPromise($this->exception);
        };

        $promise = $this->subject->handleRequest($this->request, $next, function () {
        });

        $this->assertEquals($this->exception, $promise->wait());
        $this->assertInstanceOf(Stack::class, $currentStack);
        $this->assertEquals('FormattedException', $currentStack->getResponse());
        $this->assertTrue($currentStack->isFailed());
    }

    /**
     * @expectedException \Exception
     */
    public function testOnException()
    {
        $this->collector
            ->expects($this->once())
            ->method('deactivateStack')
        ;

        $next = function () {
            throw new \Exception();
        };

        $this->subject->handleRequest($this->request, $next, function () {
        });
    }

    public function testOnError()
    {
        if (PHP_VERSION_ID <= 70000) {
            $this->markTestSkipped();
        }

        /*
         * Use the correct PHPUnit version
         * PHPUnit wrap any \Error into a \PHPUnit\Framework\Error\Warning.
         */
        if (class_exists('PHPUnit_Framework_Error')) {
            // PHPUnit 5.7
            $this->setExpectedException(\PHPUnit_Framework_Error::class);
        } else {
            // PHPUnit 6.0 and above
            $this->expectException(\PHPUnit\Framework\Error\Warning::class);
        }

        $this->collector
            ->expects($this->once())
            ->method('deactivateStack')
        ;

        $next = function () {
            return 2 / 0;
        };

        $this->subject->handleRequest($this->request, $next, function () {
        });
    }
}
