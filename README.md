# HttplugBundle

[![Latest Version](https://img.shields.io/github/release/php-http/HttplugBundle.svg?style=flat-square)](https://github.com/php-http/HttplugBundle/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/php-http/HttplugBundle.svg?style=flat-square)](https://travis-ci.org/php-http/HttplugBundle)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/php-http/HttplugBundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/php-http/HttplugBundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/php-http/HttplugBundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/php-http/HttplugBundle)
[![Total Downloads](https://img.shields.io/packagist/dt/php-http/HttplugBundle.svg?style=flat-square)](https://packagist.org/packages/php-http/HttplugBundle)

**Symfony integration for the [php-http Httplug](http://docs.httplug.io/) HTTP client**


## Install

Via Composer

``` bash
$ composer require php-http/httplug-bundle
```

Enable the bundle in your kernel:
 
``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Http\HttplugBundle\HttplugBundle(),
    );
}
```

## Usage

The usage documentation is split into two parts. First we explain how to configure the bundle in an application. The second part is for developing reusable Symfony bundles that depend on an HTTP client defined by the Httplug interface.

For information how to write applications with the services provided by this bundle, have a look at the [Httplug documentation](http://docs.httplug.io).

### Use in Applications

This bundle provides 3 services: 

* `httplug.client` a service that provides the `Http\Client\HttpClient`
* `httplug.message_factory` a service that provides the `Http\Message\MessageFactory`
* `httplug.uri_factory` a service that provides the `Http\Message\UriFactory`

These services are always an alias to another service. You can specify your own service or leave the default, which is the same name with `.default` appended. The default services in turn use the service discovery mechanism to provide the best available implementation. You can specify a class for each of the default services to use instead of discovery, as long as those classes can be instantiated without arguments.

If you need a more custom setup, define the services in your application configuration and specify your service in the `main_alias` section. For example, to add authentication headers, you could define a service that decorates the service `httplug.client.default` with a plugin that injects the authentication headers into the request and configure `httplug.main_alias.client` to the name of your service.

```yaml
httplug:
    main_alias:
        client: httplug.client.default
        message_factory: httplug.message_factory.default
        uri_factory: httplug.uri_factory.default
    classes:
        client: ~ # uses discovery if not specified
        message_factory: ~
        uri_factory: ~
```

### Use for Reusable Bundles

Rather than code against specific HTTP clients, you want to use the Httplug `Client` interface. To avoid building your own infrastructure to define services for the client, simply `require: php-http/httplug-bundle` in your bundles `composer.json`. You SHOULD provide configuration for each of your services that needs an HTTP client to specify the service to use, defaulting to `httplug.client`. This way, the default case needs no additional configuration for your users.

The only steps they need is `require` one of the adapter implementations in their projects `composer.json` and instantiating the HttplugBundle in their kernel.

## Testing

``` bash
$ composer test
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Security

If you discover any security related issues, please contact us at [security@php-http.org](mailto:security@php-http.org).


## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
