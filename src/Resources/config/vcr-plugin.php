<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Recorders
    $services->set('httplug.plugin.vcr.recorder.filesystem', 'Http\Client\Plugin\Vcr\Recorder\FilesystemRecorder')
        ->args([
            null,
            service('filesystem')->nullOnInvalid(),
        ])
        ->call('setLogger', [
            service('logger')->nullOnInvalid(),
        ])
        ->abstract();

    $services->set('httplug.plugin.vcr.recorder.in_memory', 'Http\Client\Plugin\Vcr\Recorder\InMemoryRecorder');

    // Naming strategies
    $services->set('httplug.plugin.vcr.naming_strategy.path', 'Http\Client\Plugin\Vcr\NamingStrategy\PathNamingStrategy')
        ->abstract();
};
