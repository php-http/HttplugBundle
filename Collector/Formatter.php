<?php

namespace Http\HttplugBundle\Collector;

use Exception;
use Http\Client\Exception\HttpException;
use Http\Client\Exception\TransferException;
use Http\Message\Formatter as MessageFormatter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * This class is a decorator for any Http\Message\Formatter with the the ability to format exceptions.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
class Formatter implements MessageFormatter
{
    /**
     * @var MessageFormatter
     */
    private $formatter;

    /**
     * @param MessageFormatter $formatter
     */
    public function __construct(MessageFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Formats an exception.
     *
     * @param Exception $exception
     *
     * @return string
     */
    public function formatException(Exception $exception)
    {
        if ($exception instanceof HttpException) {
            return $this->formatter->formatResponse($exception->getResponse());
        }

        if ($exception instanceof TransferException) {
            return sprintf('Transfer error: %s', $exception->getMessage());
        }

        return sprintf('Unexpected exception of type "%s": %s', get_class($exception), $exception->getMessage());
    }

    /**
     * {@inheritdoc}
     */
    public function formatRequest(RequestInterface $request)
    {
        return $this->formatter->formatRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function formatResponse(ResponseInterface $response)
    {
        return $this->formatter->formatResponse($response);
    }
}
