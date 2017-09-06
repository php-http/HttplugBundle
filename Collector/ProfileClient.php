<?php

namespace Http\HttplugBundle\Collector;

use Http\Client\Common\FlexibleHttpClient;
use Http\Client\Exception\HttpException;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * The ProfileClient decorates any client that implement both HttpClient and HttpAsyncClient interfaces to gather target
 * url and response status code.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
class ProfileClient implements HttpClient, HttpAsyncClient
{
    /**
     * @var HttpClient|HttpAsyncClient
     */
    private $client;

    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var array
     */
    private $eventNames = [];

    /**
     * @param HttpClient|HttpAsyncClient $client    The client to profile. Client must implement HttpClient or
     *                                              HttpAsyncClient interface.
     * @param Collector                  $collector
     * @param Formatter                  $formatter
     * @param Stopwatch                  $stopwatch
     */
    public function __construct($client, Collector $collector, Formatter $formatter, Stopwatch $stopwatch)
    {
        if (!($client instanceof HttpClient && $client instanceof HttpAsyncClient)) {
            $client = new FlexibleHttpClient($client);
        }

        $this->client = $client;
        $this->collector = $collector;
        $this->formatter = $formatter;
        $this->stopwatch = $stopwatch;
    }

    /**
     * {@inheritdoc}
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        $activateStack = true;
        $stack = $this->collector->getActiveStack();
        if (null === $stack) {
            //When using a discovered client not wrapped in a PluginClient, we don't have a stack from StackPlugin. So
            //we create our own stack and activate it!
            $stack = new Stack('Default', $this->formatter->formatRequest($request));
            $this->collector->addStack($stack);
            $this->collector->activateStack($stack);
            $activateStack = false;
        }

        $this->collectRequestInformations($request, $stack);
        $event = $this->stopwatch->start($this->getStopwatchEventName($request));

        $onFulfilled = function (ResponseInterface $response) use ($event, $stack) {
            $this->collectResponseInformations($response, $event, $stack);

            return $response;
        };

        $onRejected = function (\Exception $exception) use ($event, $stack) {
            $this->collectExceptionInformations($exception, $event, $stack);

            throw $exception;
        };

        $this->collector->deactivateStack($stack);

        try {
            return $this->client->sendAsyncRequest($request)->then($onFulfilled, $onRejected);
        } finally {
            $event->stop();
            if ($activateStack) {
                //We only activate the stack when created by the StackPlugin.
                $this->collector->activateStack($stack);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
        $stack = $this->collector->getActiveStack();
        if (null === $stack) {
            //When using a discovered client not wrapped in a PluginClient, we don't have a stack from StackPlugin. So
            //we create our own stack but don't activate it.
            $stack = new Stack('Default', $this->formatter->formatRequest($request));
            $this->collector->addStack($stack);
        }

        $this->collectRequestInformations($request, $stack);
        $event = $this->stopwatch->start($this->getStopwatchEventName($request));

        try {
            $response = $this->client->sendRequest($request);
            $this->collectResponseInformations($response, $event, $stack);

            return $response;
        } catch (\Exception $e) {
            $this->collectExceptionInformations($e, $event, $stack);

            throw $e;
        } catch (\Throwable $e) {
            $this->collectExceptionInformations($e, $event, $stack);

            throw $e;
        } finally {
            $event->stop();
        }
    }

    /**
     * @param RequestInterface $request
     * @param Stack            $stack
     */
    private function collectRequestInformations(RequestInterface $request, Stack $stack)
    {
        $stack->setRequestTarget($request->getRequestTarget());
        $stack->setRequestMethod($request->getMethod());
        $stack->setRequestScheme($request->getUri()->getScheme());
        $stack->setRequestHost($request->getUri()->getHost());
        $stack->setClientRequest($this->formatter->formatRequest($request));
        $stack->setCurlCommand($this->formatter->formatAsCurlCommand($request));
    }

    /**
     * @param ResponseInterface $response
     * @param StopwatchEvent    $event
     * @param Stack             $stack
     */
    private function collectResponseInformations(ResponseInterface $response, StopwatchEvent $event, Stack $stack)
    {
        $stack->setDuration($event->getDuration());
        $stack->setResponseCode($response->getStatusCode());
        $stack->setClientResponse($this->formatter->formatResponse($response));
    }

    /**
     * @param \Exception     $exception
     * @param StopwatchEvent $event
     * @param Stack          $stack
     */
    private function collectExceptionInformations(\Exception $exception, StopwatchEvent $event, Stack $stack)
    {
        if ($exception instanceof HttpException) {
            $this->collectResponseInformations($exception->getResponse(), $event, $stack);
        }

        $stack->setDuration($event->getDuration());
        $stack->setClientException($this->formatter->formatException($exception));
    }

    /**
     * Generates the event name.
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    private function getStopwatchEventName(RequestInterface $request)
    {
        $name = sprintf('%s %s', $request->getMethod(), $request->getUri());

        if (isset($this->eventNames[$name])) {
            $name .= sprintf(' [#%d]', ++$this->eventNames[$name]);
        } else {
            $this->eventNames[$name] = 1;
        }

        return $name;
    }
}
