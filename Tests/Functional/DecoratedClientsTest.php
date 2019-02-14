<?php

namespace Http\HttplugBundle\Tests\Functional;

use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
use Http\HttplugBundle\Tests\Resources\MyDecoratedHttpClient;
use Http\Mock\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class DecoratedClientsTest extends WebTestCase
{
    public function testProfilingPsr18Decoration()
    {
        static::bootKernel(['debug' => true, 'environment' => 'decorated']);
        $container = static::$kernel->getContainer();

        /**
         * @var Client
         */
        $mock = $container->get('httplug.client.mock');
        $mock->addResponse(new Response(200, [], 'OK'));

        /**
         * @var HttpClient
         */
        $client = $container->get('httplug.client.decorated');
        $this->assertInstanceOf(MyDecoratedHttpClient::class, $client);

        $response = $client->sendRequest(new \GuzzleHttp\Psr7\Request('GET', '/'));

        $this->assertSame('OK', $response->getBody()->getContents());
    }

    /**
     * {@inheritdoc}
     */
    protected static function bootKernel(array $options = [])
    {
        parent::bootKernel($options);

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::$kernel->getContainer()->get('event_dispatcher');

        $event = new GetResponseEvent(static::$kernel, new Request(), HttpKernelInterface::MASTER_REQUEST);

        $dispatcher->dispatch(KernelEvents::REQUEST, $event);

        return static::$kernel;
    }
}
