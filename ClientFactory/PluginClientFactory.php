<?php

namespace Http\HttplugBundle\ClientFactory;

use Http\Client\Plugin\PluginClient;

/**
 * This factory creates a PluginClient.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class PluginClientFactory
{
    /**
     * @param array         $plugins
     * @param ClientFactory $factory
     * @param array         $config
     *
     * @return PluginClient
     */
    public static function createPluginClient(array $plugins, ClientFactory $factory, array $config)
    {
        return new PluginClient($factory->createClient($config), $plugins);
    }
}
