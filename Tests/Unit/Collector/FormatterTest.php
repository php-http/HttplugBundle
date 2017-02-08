<?php

namespace Http\HttplugBundle\Tests\Unit\Collector;

use Http\Client\Exception\HttpException;
use Http\Client\Exception\TransferException;
use Http\HttplugBundle\Collector\Formatter;
use Http\Message\Formatter as MessageFormatter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class FormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageFormatter
     */
    private $formatter;

    /**
     * @var Formatter
     */
    private $subject;

    public function setUp()
    {
        $this->formatter = $this->getMockBuilder(MessageFormatter::class)->getMock();

        $this->subject = new Formatter($this->formatter);
    }

    public function testFormatRequest()
    {
        $request = $this->getMockBuilder(RequestInterface::class)->getMock();

        $this->formatter
            ->expects($this->once())
            ->method('formatRequest')
            ->with($this->identicalTo($request))
        ;

        $this->subject->formatRequest($request);
    }

    public function testFormatResponse()
    {
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();

        $this->formatter
            ->expects($this->once())
            ->method('formatResponse')
            ->with($this->identicalTo($response))
        ;

        $this->subject->formatResponse($response);
    }

    public function testFormatHttpException()
    {
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $exception = $this->getMockBuilder(HttpException::class)->disableOriginalConstructor()->getMock();
        $exception
            ->method('getResponse')
            ->willReturn($response)
        ;

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
        $exception = $this->getMockBuilder(TransferException::class)
            ->setConstructorArgs(['ExceptionMessage'])
            ->getMock()
        ;

        $this->assertEquals('Transfer error: ExceptionMessage', $this->subject->formatException($exception));
    }

    public function testFormatException()
    {
        $exception = new \RuntimeException('Unexpected error');
        $this->assertEquals('Unexpected exception of type "RuntimeException": Unexpected error', $this->subject->formatException($exception));
    }
}
