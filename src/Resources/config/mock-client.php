<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Http\HttplugBundle\ClientFactory\MockFactory;
use Http\Mock\Client;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('httplug.client.mock', Client::class)
        ->public();

    $services->alias(Client::class, 'httplug.client.mock')
        ->public();

    $services->set('httplug.factory.mock', MockFactory::class)
        ->call('setClient', [
            service('httplug.client.mock'),
        ]);
};
