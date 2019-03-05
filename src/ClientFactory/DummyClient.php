<?php

namespace Http\HttplugBundle\ClientFactory;

@trigger_error('The '.__NAMESPACE__.'\DummyClient interface is deprecated since version 1.7 and will be removed in 2.0.', E_USER_DEPRECATED);

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
