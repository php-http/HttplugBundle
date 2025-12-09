<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Http\Client\Common\PluginClientFactory;
use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\HttplugBundle\ClientFactory\AutoDiscoveryFactory;
use Http\HttplugBundle\ClientFactory\BuzzFactory;
use Http\HttplugBundle\ClientFactory\CurlFactory;
use Http\HttplugBundle\ClientFactory\Guzzle6Factory;
use Http\HttplugBundle\ClientFactory\Guzzle7Factory;
use Http\HttplugBundle\ClientFactory\ReactFactory;
use Http\HttplugBundle\ClientFactory\SocketFactory;
use Http\HttplugBundle\ClientFactory\SymfonyFactory;
use Http\HttplugBundle\Discovery\ConfiguredClientsStrategy;
use Http\HttplugBundle\Discovery\ConfiguredClientsStrategyListener;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('httplug.strategy', ConfiguredClientsStrategy::class)
        ->args([
            service('httplug.auto_discovery.auto_discovered_client')->nullOnInvalid(),
            service('httplug.auto_discovery.auto_discovered_async')->nullOnInvalid(),
        ]);

    $services->set('httplug.strategy_listener', ConfiguredClientsStrategyListener::class)
        ->tag('kernel.event_subscriber');

    $services->set('httplug.auto_discovery.auto_discovered_client', ClientInterface::class)
        ->factory([Psr18ClientDiscovery::class, 'find']);

    $services->set('httplug.auto_discovery.auto_discovered_async', HttpAsyncClient::class)
        ->factory([HttpAsyncClientDiscovery::class, 'find']);

    // Discovery with autowiring support
    $services->set('httplug.async_client.default', HttpAsyncClient::class)
        ->factory([HttpAsyncClientDiscovery::class, 'find']);

    $services->alias(HttpAsyncClient::class, 'httplug.async_client.default');

    $services->set('httplug.client.default', ClientInterface::class)
        ->factory([Psr18ClientDiscovery::class, 'find']);

    $services->alias(ClientInterface::class, 'httplug.client');

    // Discovery for PSR-18
    $services->set('httplug.psr18_client.default', ClientInterface::class)
        ->factory([Psr18ClientDiscovery::class, 'find']);

    // Discovery for PSR-17
    $services->set('httplug.psr17_request_factory.default', RequestFactoryInterface::class)
        ->factory([Psr17FactoryDiscovery::class, 'findRequestFactory']);

    $services->alias(RequestFactoryInterface::class, 'httplug.psr17_request_factory.default')
        ->public();

    $services->set('httplug.psr17_response_factory.default', ResponseFactoryInterface::class)
        ->factory([Psr17FactoryDiscovery::class, 'findResponseFactory']);

    $services->alias(ResponseFactoryInterface::class, 'httplug.psr17_response_factory.default')
        ->public();

    $services->set('httplug.psr17_stream_factory.default', StreamFactoryInterface::class)
        ->factory([Psr17FactoryDiscovery::class, 'findStreamFactory']);

    $services->alias(StreamFactoryInterface::class, 'httplug.psr17_stream_factory.default')
        ->public();

    $services->set('httplug.psr17_uri_factory.default', UriFactoryInterface::class)
        ->factory([Psr17FactoryDiscovery::class, 'findUrlFactory']);

    $services->alias(UriFactoryInterface::class, 'httplug.psr17_uri_factory.default')
        ->public();

    $services->set('httplug.psr17_uploaded_file_factory.default', UploadedFileFactoryInterface::class)
        ->factory([Psr17FactoryDiscovery::class, 'findUploadedFileFactory']);

    $services->alias(UploadedFileFactoryInterface::class, 'httplug.psr17_uploaded_file_factory.default')
        ->public();

    $services->set('httplug.psr17_server_request_factory.default', ServerRequestFactoryInterface::class)
        ->factory([Psr17FactoryDiscovery::class, 'findServerRequestFactory']);

    $services->alias(ServerRequestFactoryInterface::class, 'httplug.psr17_server_request_factory.default')
        ->public();

    // PluginClientFactory
    $services->set(PluginClientFactory::class);

    // ClientFactories
    $services->set('httplug.factory.auto', AutoDiscoveryFactory::class);

    $services->set('httplug.factory.buzz', BuzzFactory::class)
        ->args([
            service('httplug.psr17_response_factory'),
        ]);

    $services->set('httplug.factory.curl', CurlFactory::class)
        ->args([
            service('httplug.psr17_response_factory'),
            service('httplug.psr17_stream_factory'),
        ]);

    $services->set('httplug.factory.guzzle6', Guzzle6Factory::class);

    $services->set('httplug.factory.guzzle7', Guzzle7Factory::class);

    $services->set('httplug.factory.react', ReactFactory::class);

    $services->set('httplug.factory.socket', SocketFactory::class);

    $services->set('httplug.factory.symfony', SymfonyFactory::class)
        ->args([
            service('httplug.psr17_response_factory'),
            service('httplug.psr17_stream_factory'),
        ]);
};
