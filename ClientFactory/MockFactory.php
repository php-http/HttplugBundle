<?php

namespace Http\HttplugBundle\ClientFactory;

use Http\Mock\Client;

/**
 * @author Gary PEGEOT <garypegeot@gmail.com>
 */
class MockFactory implements ClientFactory
{
    /**
     * {@inheritdoc}
     */
    public function createClient(array $config = [])
    {
        if (!class_exists(Client::class)) {
            throw new \LogicException('To use the mock adapter you need to install the "php-http/mock-client" package.');
        }

        return new Client();
    }
}
