<?php

namespace Http\HttplugBundle\ClientFactory;

use Http\Client\HttpClient;
use Http\Mock\Client;

/**
 * @author Gary PEGEOT <garypegeot@gmail.com>
 */
final class MockFactory implements ClientFactory
{
    /**
     * @var HttpClient
     */
    private $client;

    /**
     * Set the client instance that this factory should return.
     *
     * Note that this can be any client, not only a mock client.
     */
    public function setClient(HttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function createClient(array $config = [])
    {
        if (!class_exists(Client::class)) {
            throw new \LogicException('To use the mock adapter you need to install the "php-http/mock-client" package.');
        }

        if (!$this->client) {
            $this->client = new Client();
        }

        return $this->client;
    }
}
