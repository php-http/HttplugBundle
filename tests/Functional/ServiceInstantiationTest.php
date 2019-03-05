<?php

namespace Http\HttplugBundle\Tests\Functional;

use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\ProfileClient;
use Http\HttplugBundle\Collector\ProfilePlugin;
use Http\HttplugBundle\Collector\StackPlugin;
use Nyholm\NSA;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class ServiceInstantiationTest extends WebTestCase
{
    public function testHttpClient()
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has('httplug.client'));
        $client = $container->get('httplug.client');
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testHttpClientNoDebug()
    {
        static::bootKernel(['debug' => false]);
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has('httplug.client'));
        $client = $container->get('httplug.client');
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testDebugToolbar()
    {
        static::bootKernel(['debug' => true]);
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has('profiler'));
        $profiler = $container->get('profiler');
        $this->assertInstanceOf(Profiler::class, $profiler);
        $this->assertTrue($profiler->has('httplug'));
        $collector = $profiler->get('httplug');
        $this->assertInstanceOf(Collector::class, $collector);
    }

    public function testProfilingShouldNotChangeServiceReference()
    {
        static::bootKernel(['debug' => true]);
        $container = static::$kernel->getContainer();

        $this->assertInstanceof(RedirectPlugin::class, $container->get('app.http.plugin.custom'));
    }

    public function testProfilingDecoration()
    {
        static::bootKernel(['debug' => true]);
        $container = static::$kernel->getContainer();

        $client = $container->get('httplug.client.acme');

        $this->assertInstanceOf(PluginClient::class, $client);
        $this->assertInstanceOf(ProfileClient::class, NSA::getProperty($client, 'client'));

        $plugins = NSA::getProperty($client, 'plugins');

        $this->assertInstanceOf(StackPlugin::class, $plugins[0]);
        $this->assertInstanceOf(ProfilePlugin::class, $plugins[1]);
        $this->assertInstanceOf(ProfilePlugin::class, $plugins[2]);
        $this->assertInstanceOf(ProfilePlugin::class, $plugins[3]);
        $this->assertInstanceOf(ProfilePlugin::class, $plugins[4]);
    }

    public function testProfilingPsr18Decoration()
    {
        if (!interface_exists(ClientInterface::class)) {
            $this->markTestSkipped('PSR-18 is not installed');
        }

        static::bootKernel(['debug' => true, 'environment' => 'psr18']);
        $container = static::$kernel->getContainer();

        $client = $container->get('httplug.client.my_psr18');
        $this->assertInstanceOf(PluginClient::class, $client);
        $profileClient = NSA::getProperty($client, 'client');
        $this->assertInstanceOf(ProfileClient::class, $profileClient);

        $flexibleClient = NSA::getProperty($profileClient, 'client');
        $psr18Client = NSA::getProperty($flexibleClient, 'httpClient');
        $this->assertInstanceOf(ClientInterface::class, $psr18Client);

        $response = $client->sendRequest(new \GuzzleHttp\Psr7\Request('GET', 'https://example.com'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
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
