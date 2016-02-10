<?php

namespace Http\HttplugBundle\ClientFactory;

use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as Adapter;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Guzzle6Factory implements ClientFactory
{
    /**
     * {@inheritdoc}
     */
    public function createClient(array $config = [])
    {
        if (!class_exists('Http\Adapter\Guzzle6\Client')) {
            throw new \LogicException('To use the Guzzle6 adapter you need to install the "php-http/guzzle6-adapter" package.');
        }

        $client = new Client($config);

        return new Adapter($client);
    }
}
