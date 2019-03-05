<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\Collector;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\HttpException;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\StackPlugin;
use Http\Message\Formatter as MessageFormatter;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use PHPUnit\Framework\Error\Warning;
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

    public function setUp(): void
    {
        $this->collector = new Collector();
        $messageFormatter = $this->createMock(MessageFormatter::class);
        $this->formatter = new Formatter($messageFormatter, $this->createMock(MessageFormatter::class));
        $this->request = new Request('GET', '/');
        $this->response = new Response();
        $this->exception = new HttpException('', $this->request, $this->response);

        $messageFormatter
            ->method('formatRequest')
            ->with($this->request)
            ->willReturn('FormattedRequest')
        ;

        $messageFormatter
            ->method('formatResponse')
            ->with($this->response)
            ->willReturn('FormattedResponse')
        ;

        $this->subject = new StackPlugin($this->collector, $this->formatter, 'default');
    }

    public function testStackIsInitialized(): void
    {
        $next = function () {
            return new FulfilledPromise($this->response);
        };

        $this->subject->handleRequest($this->request, $next, function (): void {
        });
        $stack = $this->collector->getActiveStack();
        $this->assertEquals('default', $stack->getClient());
        $this->assertEquals('FormattedRequest', $stack->getRequest());
    }

    public function testOnFulfilled(): void
    {
        $next = function () {
            return new FulfilledPromise($this->response);
        };

        $promise = $this->subject->handleRequest($this->request, $next, function (): void {
        });

        $this->assertEquals($this->response, $promise->wait());
        $currentStack = $this->collector->getActiveStack();
        $this->assertEquals('FormattedResponse', $currentStack->getResponse());
    }

    public function testOnRejected(): void
    {
        $next = function () {
            return new RejectedPromise($this->exception);
        };

        $promise = $this->subject->handleRequest($this->request, $next, function (): void {
        });

        $this->assertEquals($this->exception, $promise->wait());
        $currentStack = $this->collector->getActiveStack();
        $this->assertEquals('FormattedResponse', $currentStack->getResponse());
        $this->assertTrue($currentStack->isFailed());
    }

    public function testOnException(): void
    {
        $next = function (): void {
            throw new \Exception();
        };

        $this->expectException(\Exception::class);
        $this->subject->handleRequest($this->request, $next, function (): void {
        });
    }

    public function testOnError(): void
    {
        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            $this->expectException(\DivisionByZeroError::class);
        } else {
            $this->expectException(Warning::class);
        }

        $next = function () {
            return 2 / 0;
        };

        $this->subject->handleRequest($this->request, $next, function (): void {
        });
    }
}
