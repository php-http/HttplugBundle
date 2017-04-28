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
     * @param HttpClient|HttpAsyncClient $client    The client to profile. Client must implement both HttpClient and
     *                                              HttpAsyncClient interfaces.
     * @param Collector                  $collector
     * @param Formatter                  $formatter
     * @param Stopwatch                  $stopwatch
     */
    public function __construct($client, Collector $collector, Formatter $formatter, Stopwatch $stopwatch)
    {
        if (!($client instanceof HttpClient && $client instanceof HttpAsyncClient)) {
            throw new \RuntimeException(sprintf(
                '%s first argument must implement %s and %s. Consider using %s.',
                    __METHOD__,
                    HttpClient::class,
                    HttpAsyncClient::class,
                    FlexibleHttpClient::class
            ));
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
        $stack = $this->collector->getCurrentStack();
        $this->collectRequestInformations($request, $stack);
        $event = $this->stopwatch->start($this->getStopwatchEventName($request));

        return $this->client->sendAsyncRequest($request)->then(
            function (ResponseInterface $response) use ($event, $stack) {
                $event->stop();
                $this->collectResponseInformations($response, $event, $stack);

                return $response;
            }, function (\Exception $exception) use ($event, $stack) {
                $event->stop();
                $this->collectExceptionInformations($exception, $event, $stack);

                throw $exception;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
        $stack = $this->collector->getCurrentStack();
        $this->collectRequestInformations($request, $stack);
        $event = $this->stopwatch->start($this->getStopwatchEventName($request));

        try {
            $response = $this->client->sendRequest($request);
            $event->stop();

            $this->collectResponseInformations($response, $event, $stack);

            return $response;
        } catch (\Exception $e) {
            $event->stop();
            $this->collectExceptionInformations($e, $event, $stack);

            throw $e;
        }
    }

    /**
     * @param RequestInterface $request
     * @param Stack|null       $stack
     */
    private function collectRequestInformations(RequestInterface $request, Stack $stack = null)
    {
        if (!$stack) {
            return;
        }

        $stack->setRequestTarget($request->getRequestTarget());
        $stack->setRequestMethod($request->getMethod());
        $stack->setRequestScheme($request->getUri()->getScheme());
        $stack->setRequestHost($request->getUri()->getHost());
        $stack->setClientRequest($this->formatter->formatRequest($request));
    }

    /**
     * @param ResponseInterface $response
     * @param StopwatchEvent    $event
     * @param Stack|null        $stack
     */
    private function collectResponseInformations(ResponseInterface $response, StopwatchEvent $event, Stack $stack = null)
    {
        if (!$stack) {
            return;
        }

        $stack->setDuration($event->getDuration());
        $stack->setResponseCode($response->getStatusCode());
        $stack->setClientResponse($this->formatter->formatResponse($response));
    }

    /**
     * @param \Exception     $exception
     * @param StopwatchEvent $event
     * @param Stack|null     $stack
     */
    private function collectExceptionInformations(\Exception $exception, StopwatchEvent $event, Stack $stack = null)
    {
        if ($exception instanceof HttpException) {
            $this->collectResponseInformations($exception->getResponse(), $event, $stack);
        }

        if (!$stack) {
            return;
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
