<?php

namespace Http\HttplugBundle\Tests\Unit\Collector;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\HttpException;
use Http\Client\Exception\TransferException;
use Http\HttplugBundle\Collector\Formatter;
use Http\Message\Formatter as MessageFormatter;
use Http\Message\Formatter\CurlCommandFormatter;
use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase
{
    /**
     * @var MessageFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formatter;

    /**
     * @var CurlCommandFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $curlFormatter;

    /**
     * @var Formatter
     */
    private $subject;

    public function setUp()
    {
        $this->formatter = $this->getMockBuilder(MessageFormatter::class)->getMock();
        $this->curlFormatter = $this->getMockBuilder(CurlCommandFormatter::class)->getMock();

        $this->subject = new Formatter($this->formatter, $this->curlFormatter);
    }

    public function testFormatRequest()
    {
        $request = new Request('GET', '/');

        $this->formatter
            ->expects($this->once())
            ->method('formatRequest')
            ->with($this->identicalTo($request))
        ;

        $this->subject->formatRequest($request);
    }

    public function testFormatResponse()
    {
        $response = new Response();

        $this->formatter
            ->expects($this->once())
            ->method('formatResponse')
            ->with($this->identicalTo($response))
        ;

        $this->subject->formatResponse($response);
    }

    public function testFormatHttpException()
    {
        $request = new Request('GET', '/');
        $response = new Response();
        $exception = new HttpException('', $request, $response);

        $this->formatter
            ->expects($this->once())
            ->method('formatResponse')
            ->with($this->identicalTo($response))
            ->willReturn('FormattedException')
        ;

        $this->assertEquals('FormattedException', $this->subject->formatException($exception));
    }

    public function testFormatTransferException()
    {
        $exception = new TransferException('ExceptionMessage');

        $this->assertEquals('Transfer error: ExceptionMessage', $this->subject->formatException($exception));
    }

    public function testFormatException()
    {
        $exception = new \RuntimeException('Unexpected error');
        $this->assertEquals('Unexpected exception of type "RuntimeException": Unexpected error', $this->subject->formatException($exception));
    }

    public function testFormatAsCurlCommand()
    {
        $request = new Request('GET', '/');

        $this->curlFormatter
            ->expects($this->once())
            ->method('formatRequest')
            ->with($this->identicalTo($request))
            ->willReturn('curl -L http://example.com')
        ;

        $this->assertEquals('curl -L http://example.com', $this->subject->formatAsCurlCommand($request));
    }
}
