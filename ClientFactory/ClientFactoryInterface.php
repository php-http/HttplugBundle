<?php

namespace Http\HttplugBundle\ClientFactory;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface ClientFactoryInterface
{
    /**
     * Input an array of configuration to be able to create a HttpClient.
     *
     * @param array $config
     *
     * @return \Http\Client\HttpClient
     */
    public function createClient(array $config = []);
}
