<?php

namespace Http\HttplugBundle\ClientFactory;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;

/**
 * This client is used as a placeholder for the dependency injection. It will never be used.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface DummyClient extends HttpClient, HttpAsyncClient
{
}
