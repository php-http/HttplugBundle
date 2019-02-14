<?php

namespace Http\HttplugBundle\Tests\Resources;

use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;

class MyDecoratedHttpClient implements HttpClient
{
    /**
     * @var HttpClient
     */
    private $client;

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    public function sendRequest(RequestInterface $request)
    {
        return $this->client->sendRequest($request);
    }
}
