<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Client\Common\Plugin\AddPathPlugin;
use Http\Client\Common\Plugin\BaseUriPlugin;
use Http\Client\Common\Plugin\CachePlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\ContentTypePlugin;
use Http\Client\Common\Plugin\CookiePlugin;
use Http\Client\Common\Plugin\DecoderPlugin;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\Plugin\HeaderAppendPlugin;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Http\Client\Common\Plugin\HeaderRemovePlugin;
use Http\Client\Common\Plugin\HeaderSetPlugin;
use Http\Client\Common\Plugin\HistoryPlugin;
use Http\Client\Common\Plugin\LoggerPlugin;
use Http\Client\Common\Plugin\QueryDefaultsPlugin;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\Plugin\RequestSeekableBodyPlugin;
use Http\Client\Common\Plugin\ResponseSeekableBodyPlugin;
use Http\Client\Common\Plugin\RetryPlugin;
use Http\Client\Common\Plugin\StopwatchPlugin;
use Http\Client\Common\Plugin\ThrottlePlugin;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('httplug.plugin.cache', CachePlugin::class)
        ->args([
            null,
            null,
            null,
        ])
        ->abstract();

    $services->set('httplug.plugin.content_length', ContentLengthPlugin::class);

    $services->set('httplug.plugin.cookie', CookiePlugin::class)
        ->args([null]);

    $services->set('httplug.plugin.decoder', DecoderPlugin::class);

    $services->set('httplug.plugin.error', ErrorPlugin::class);

    $services->set('httplug.plugin.history', HistoryPlugin::class)
        ->args([null]);

    $services->set('httplug.plugin.logger', LoggerPlugin::class)
        ->args([
            null,
            null,
        ])
        ->tag('monolog.logger', ['channel' => 'httplug'])
        ->abstract();

    $services->set('httplug.plugin.redirect', RedirectPlugin::class);

    $services->set('httplug.plugin.retry', RetryPlugin::class);

    $services->set('httplug.plugin.stopwatch', StopwatchPlugin::class)
        ->args([null])
        ->abstract();

    $services->set('httplug.plugin.throttle', ThrottlePlugin::class)
        ->args([null])
        ->abstract();

    // client specific plugin definition prototypes

    $services->set('httplug.plugin.add_host', AddHostPlugin::class)
        ->args([
            null,
            null,
        ])
        ->abstract();

    $services->set('httplug.plugin.add_path', AddPathPlugin::class)
        ->args([null])
        ->abstract();

    $services->set('httplug.plugin.base_uri', BaseUriPlugin::class)
        ->args([
            null,
            null,
        ])
        ->abstract();

    $services->set('httplug.plugin.content_type', ContentTypePlugin::class)
        ->args([null])
        ->abstract();

    $services->set('httplug.plugin.header_append', HeaderAppendPlugin::class)
        ->args([null])
        ->abstract();

    $services->set('httplug.plugin.header_defaults', HeaderDefaultsPlugin::class)
        ->args([null])
        ->abstract();

    $services->set('httplug.plugin.header_set', HeaderSetPlugin::class)
        ->args([null])
        ->abstract();

    $services->set('httplug.plugin.header_remove', HeaderRemovePlugin::class)
        ->args([null])
        ->abstract();

    $services->set('httplug.plugin.query_defaults', QueryDefaultsPlugin::class)
        ->args([null])
        ->abstract();

    $services->set('httplug.plugin.request_seekable_body', RequestSeekableBodyPlugin::class)
        ->args([null])
        ->abstract();

    $services->set('httplug.plugin.response_seekable_body', ResponseSeekableBodyPlugin::class)
        ->args([null])
        ->abstract();
};
