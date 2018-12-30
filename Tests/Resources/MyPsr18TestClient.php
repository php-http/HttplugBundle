<?php

namespace Http\HttplugBundle\Tests\Resources;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MyPsr18TestClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return new Response();
    }

}
