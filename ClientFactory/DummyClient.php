<?php

namespace Http\HttplugBundle\ClientFactory;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;

/**
 * This client is used as a placeholder for the dependency injection. It will never be used.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class DummyClient implements HttpClient, HttpAsyncClient
{
    public function sendAsyncRequest(RequestInterface $request)
    {
    }

    public function sendRequest(RequestInterface $request)
    {
    }
}
