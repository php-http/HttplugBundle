<?php

namespace Http\HttplugBundle\ClientFactory;

use Http\Client\HttpClient;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface ClientFactory
{
    /**
     * Input an array of configuration to be able to create a HttpClient.
     *
     * @param array $config
     *
     * @return HttpClient
     */
    public function createClient(array $config = []);
}
