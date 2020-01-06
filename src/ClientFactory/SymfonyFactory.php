<?php

namespace Http\HttplugBundle\ClientFactory;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttplugClient;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SymfonyFactory implements ClientFactory
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface   $streamFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createClient(array $config = [])
    {
        if (!class_exists(HttplugClient::class)) {
            throw new \LogicException('To use the Symfony client you need to install the "symfony/http-client" package.');
        }

        return new HttplugClient(HttpClient::create($config), $this->responseFactory, $this->streamFactory);
    }
}
