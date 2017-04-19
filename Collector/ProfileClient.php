<?php

namespace Http\HttplugBundle\Collector;

use Http\Client\Common\FlexibleHttpClient;
use Http\Client\Exception\HttpException;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
     * @param HttpClient|HttpAsyncClient $client    The client to profile. Client must implement both HttpClient and
     *                                              HttpAsyncClient interfaces.
     * @param Collector                  $collector
     * @param Formatter                  $formatter
     */
    public function __construct($client, Collector $collector, Formatter $formatter)
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
    }

    /**
     * {@inheritdoc}
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        $stack = $this->collector->getCurrentStack();
        $this->collectRequestInformations($request, $stack);

        return $this->client->sendAsyncRequest($request)->then(function (ResponseInterface $response) use ($stack) {
            $this->collectResponseInformations($response, $stack);

            return $response;
        }, function (\Exception $exception) use ($stack) {
            $this->collectExceptionInformations($exception, $stack);

            throw $exception;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
        $stack = $this->collector->getCurrentStack();
        $this->collectRequestInformations($request, $stack);

        try {
            $response = $this->client->sendRequest($request);

            $this->collectResponseInformations($response, $stack);

            return $response;
        } catch (\Exception $e) {
            $this->collectExceptionInformations($e, $stack);

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
     * @param Stack|null        $stack
     */
    private function collectResponseInformations(ResponseInterface $response, Stack $stack = null)
    {
        if (!$stack) {
            return;
        }

        $stack->setResponseCode($response->getStatusCode());
        $stack->setClientResponse($this->formatter->formatResponse($response));
    }

    /**
     * @param \Exception $exception
     * @param Stack|null $stack
     */
    private function collectExceptionInformations(\Exception $exception, Stack $stack = null)
    {
        if ($exception instanceof HttpException) {
            $this->collectResponseInformations($exception->getResponse(), $stack);
        }

        if (!$stack) {
            return;
        }

        $stack->setClientException($this->formatter->formatException($exception));
    }
}
