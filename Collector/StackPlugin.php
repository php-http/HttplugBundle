<?php

namespace Http\HttplugBundle\Collector;

use Exception;
use Http\Client\Common\Plugin;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The StackPlugin must be used as first Plugin in a client stack. It's used to detect when a new request start by
 * creating a new Stack and pushing it to the Collector.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
class StackPlugin implements Plugin
{
    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var string
     */
    private $client;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @param Collector $collector
     * @param Formatter $formatter
     * @param string    $client
     */
    public function __construct(Collector $collector, Formatter $formatter, $client)
    {
        $this->collector = $collector;
        $this->formatter = $formatter;
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first)
    {
        $stack = new Stack($this->client, $this->formatter->formatRequest($request));

        $this->collector->addStack($stack);
        $this->collector->activateStack($stack);

        $onFulfilled = function (ResponseInterface $response) use ($stack) {
            $stack->setResponse($this->formatter->formatResponse($response));

            return $response;
        };

        $onRejected = function (Exception $exception) use ($stack) {
            $stack->setResponse($this->formatter->formatException($exception));
            $stack->setFailed(true);

            throw $exception;
        };

        try {
            return $next($request)->then($onFulfilled, $onRejected);
        } finally {
            $this->collector->deactivateStack($stack);
        }
    }
}
