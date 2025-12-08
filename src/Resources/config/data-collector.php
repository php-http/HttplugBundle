<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\PluginClientFactory;
use Http\HttplugBundle\Collector\PluginClientFactoryListener;
use Http\HttplugBundle\Collector\ProfileClient;
use Http\HttplugBundle\Collector\ProfileClientFactory;
use Http\HttplugBundle\Collector\StackPlugin;
use Http\HttplugBundle\Collector\Twig\HttpMessageMarkupExtension;
use Http\Message\Formatter\CurlCommandFormatter;
use Http\Message\Formatter\FullHttpMessageFormatter;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('httplug.formatter.full_http_message', FullHttpMessageFormatter::class);

    $services->set('httplug.collector.formatter', Formatter::class)
        ->args([
            service('httplug.formatter.full_http_message'),
            inline_service(CurlCommandFormatter::class),
        ]);

    $services->set('httplug.collector.collector', Collector::class)
        ->tag('data_collector', [
            'template' => '@Httplug/webprofiler.html.twig',
            'priority' => 200,
            'id' => 'httplug',
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('httplug.plugin.stack', StackPlugin::class)
        ->args([
            service('httplug.collector.collector'),
            service('httplug.collector.formatter'),
        ])
        ->abstract();

    $services->set('httplug.collector.twig.http_message', HttpMessageMarkupExtension::class)
        ->args([
            service('var_dumper.cloner')->nullOnInvalid(),
            service('var_dumper.html_dumper')->nullOnInvalid(),
        ])
        ->tag('twig.extension');

    // Discovered clients
    $services->set('httplug.collector.auto_discovered_client', ProfileClient::class)
        ->decorate('httplug.auto_discovery.auto_discovered_client')
        ->args([
            service('httplug.collector.auto_discovered_client.inner'),
            service('httplug.collector.collector'),
            service('httplug.collector.formatter'),
            service('debug.stopwatch'),
        ]);

    $services->set('httplug.collector.auto_discovered_async', ProfileClient::class)
        ->decorate('httplug.auto_discovery.auto_discovered_async')
        ->args([
            service('httplug.collector.auto_discovered_async.inner'),
            service('httplug.collector.collector'),
            service('httplug.collector.formatter'),
            service('debug.stopwatch'),
        ]);

    // ClientFactories
    $services->set('httplug.collector.factory.auto', ProfileClientFactory::class)
        ->decorate('httplug.factory.auto')
        ->args([
            service('httplug.collector.factory.auto.inner'),
            service('httplug.collector.collector'),
            service('httplug.collector.formatter'),
            service('debug.stopwatch'),
        ]);

    $services->set('httplug.collector.factory.buzz', ProfileClientFactory::class)
        ->decorate('httplug.factory.buzz')
        ->args([
            service('httplug.collector.factory.buzz.inner'),
            service('httplug.collector.collector'),
            service('httplug.collector.formatter'),
            service('debug.stopwatch'),
        ]);

    $services->set('httplug.collector.factory.curl', ProfileClientFactory::class)
        ->decorate('httplug.factory.curl')
        ->args([
            service('httplug.collector.factory.curl.inner'),
            service('httplug.collector.collector'),
            service('httplug.collector.formatter'),
            service('debug.stopwatch'),
        ]);

    $services->set('httplug.collector.factory.guzzle6', ProfileClientFactory::class)
        ->decorate('httplug.factory.guzzle6')
        ->args([
            service('httplug.collector.factory.guzzle6.inner'),
            service('httplug.collector.collector'),
            service('httplug.collector.formatter'),
            service('debug.stopwatch'),
        ]);

    $services->set('httplug.collector.factory.guzzle7', ProfileClientFactory::class)
        ->decorate('httplug.factory.guzzle7')
        ->args([
            service('httplug.collector.factory.guzzle7.inner'),
            service('httplug.collector.collector'),
            service('httplug.collector.formatter'),
            service('debug.stopwatch'),
        ]);

    $services->set('httplug.collector.factory.react', ProfileClientFactory::class)
        ->decorate('httplug.factory.react')
        ->args([
            service('httplug.collector.factory.react.inner'),
            service('httplug.collector.collector'),
            service('httplug.collector.formatter'),
            service('debug.stopwatch'),
        ]);

    $services->set('httplug.collector.factory.socket', ProfileClientFactory::class)
        ->decorate('httplug.factory.socket')
        ->args([
            service('httplug.collector.factory.socket.inner'),
            service('httplug.collector.collector'),
            service('httplug.collector.formatter'),
            service('debug.stopwatch'),
        ]);

    $services->set(\Http\Client\Common\PluginClientFactory::class, PluginClientFactory::class)
        ->args([
            service('httplug.collector.collector'),
            service('httplug.collector.formatter'),
            service('debug.stopwatch'),
        ]);

    $services->set(PluginClientFactoryListener::class)
        ->args([
            service(\Http\Client\Common\PluginClientFactory::class),
        ])
        ->tag('kernel.event_subscriber');
};
