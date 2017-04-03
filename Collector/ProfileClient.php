<?php

namespace Http\HttplugBundle\Collector;

use Http\Client\Common\FlexibleHttpClient;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;

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
     * @param HttpClient|HttpAsyncClient $client    The client to profile. Client must implement both HttpClient and
     *                                              HttpAsyncClient interfaces.
     * @param Collector                  $collector
     */
    public function __construct($client, Collector $collector)
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
    }

    /**
     * {@inheritdoc}
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        $this->collectRequestInformations($request);

        return $this->client->sendAsyncRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
        $this->collectRequestInformations($request);

        return $this->client->sendRequest($request);
    }

    /**
     * @param RequestInterface $request
     */
    private function collectRequestInformations(RequestInterface $request)
    {
        if (!$stack = $this->collector->getCurrentStack()) {
            return;
        }

        $stack = $this->collector->getCurrentStack();
        $stack->setRequestTarget($request->getRequestTarget());
        $stack->setRequestMethod($request->getMethod());
    }
}
