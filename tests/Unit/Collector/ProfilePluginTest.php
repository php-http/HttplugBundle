<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\Collector;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Common\Plugin;
use Http\Client\Exception\TransferException;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\ProfilePlugin;
use Http\HttplugBundle\Collector\Stack;
use Http\Message\Formatter as MessageFormatter;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Http\Promise\RejectedPromise;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ProfilePluginTest extends TestCase
{
    /**
     * @var Plugin|MockObject
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

    public function setUp(): void
    {
        $this->collector = new Collector();
        $messageFormatter = $this->createMock(MessageFormatter::class);
        $this->formatter = new Formatter($messageFormatter, $this->createMock(MessageFormatter::class));

        $this->plugin = $this->getMockBuilder(Plugin::class)->getMock();
        $this->request = new Request('GET', '/');
        $this->response = new Response();
        $this->fulfilledPromise = new FulfilledPromise($this->response);
        $this->currentStack = new Stack('default', 'FormattedRequest');
        $this->exception = new TransferException();
        $this->rejectedPromise = new RejectedPromise($this->exception);

        $this->plugin
            ->method('handleRequest')
            ->willReturnCallback(function ($request, $next, $first) {
                return $next($request);
            })
        ;

        $messageFormatter
            ->method('formatRequest')
            ->with($this->identicalTo($this->request))
            ->willReturn('FormattedRequest')
        ;

        $messageFormatter
            ->method('formatResponse')
            ->with($this->identicalTo($this->response))
            ->willReturn('FormattedResponse')
        ;

        $this->subject = new ProfilePlugin(
            $this->plugin,
            $this->collector,
            $this->formatter
        );
    }

    public function testCallDecoratedPlugin(): void
    {
        $this->plugin
            ->expects($this->once())
            ->method('handleRequest')
            ->with($this->request)
        ;

        $this->subject->handleRequest($this->request, function () {
            return $this->fulfilledPromise;
        }, function (): void {
        });
    }

    public function testProfileIsInitialized(): void
    {
        $this->subject->handleRequest($this->request, function () {
            return $this->fulfilledPromise;
        }, function (): void {
        });

        $this->assertCount(1, $this->currentStack->getProfiles());
        $profile = $this->currentStack->getProfiles()[0];
        $this->assertEquals(get_class($this->plugin), $profile->getPlugin());
    }

    public function testCollectRequestInformations(): void
    {
        $this->subject->handleRequest($this->request, function () {
            return $this->fulfilledPromise;
        }, function (): void {
        });

        $profile = $this->currentStack->getProfiles()[0];
        $this->assertEquals('FormattedRequest', $profile->getRequest());
    }

    public function testOnFulfilled(): void
    {
        $promise = $this->subject->handleRequest($this->request, function () {
            return $this->fulfilledPromise;
        }, function (): void {
        });

        $this->assertEquals($this->response, $promise->wait());
        $profile = $this->currentStack->getProfiles()[0];
        $this->assertEquals('FormattedResponse', $profile->getResponse());
    }

    public function testOnRejected(): void
    {
        $promise = $this->subject->handleRequest($this->request, function () {
            return $this->rejectedPromise;
        }, function (): void {
        });

        $this->assertEquals($this->exception, $promise->wait());
        $profile = $this->currentStack->getProfiles()[0];
        $this->expectException(TransferException::class);
        $profile->getResponse();
    }
}
