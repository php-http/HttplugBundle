# PhpHttpBundle

[![Latest Version](https://img.shields.io/github/release/php-http/PhpHttpBundle.svg?style=flat-square)](https://github.com/php-http/PhpHttpBundle/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/php-http/PhpHttpBundle.svg?style=flat-square)](https://travis-ci.org/php-http/PhpHttpBundle)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/php-http/PhpHttpBundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/php-http/PhpHttpBundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/php-http/PhpHttpBundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/php-http/PhpHttpBundle)
[![Total Downloads](https://img.shields.io/packagist/dt/php-http/PhpHttpBundle.svg?style=flat-square)](https://packagist.org/packages/php-http/PhpHttpBundle)

**Symfony integration for the [php-http](http://php-http.readthedocs.org/) system**

This is early work in progress for now. See the issues for a plan and discussion what should be done.


## Install

Via Composer

``` bash
$ composer require php-http/php-http-bundle
```

Enable the bundle in your kernel:
 
``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Http\ClientBundle\HttpClientBundle(),
    );
}
```

## Usage

TODO: move this to php-http-documentation? or keep it here?

The usage documentation is split into two parts. First we explain how to configure the bundle in an application. The second part is for developing reusable Symfony bundles that depend on an HTTP client defined by the php-http interface.

### Use in applications

This bundle provides 3 services: 

* `php_http.client` a service that provides the `Http\Adapter\HttpAdapter` (TODO: proper interface?)
* `php_http.message_factory` a service that provides the `Http\Message\MessageFactory`
* `php_http.uri_factory` a service that provides the `Http\Message\UriFactory`

These services are always an alias to another service. You can specify your own service or leave the default, which is the same name with `.default` appended. The default services in turn use the service discovery mechanism to provide the best available implementation. You can specify a class for each of the default services to use instead of discovery. 

If you need to customize the service with decorators, e.g. to add authentication headers, decorate the default service and configure the main alias to your decorating service name.

```yaml
http_client:
    main_alias:
        client: php_http.client.default
        message_factory: php_http.message_factory.default
        uri_factory: php_http.uri_factory.default
    classes:
        client: ~ # uses discovery if not specified
        message_factory: ~
        uri_factory: ~
```

### Use for reusable bundles

Rather than code against specific HTTP clients, you want to use the php-http client interface. To avoid building your own infrastructure to define services for the client, simply `require: php-http/http-client-bundle` in your bundles `composer.json`. You SHOULD provide configuration for each of your services that needs an HTTP client to specify the service to use, defaulting to `php_http.client`. This way, the default case needs no additional configuration for your users.

The only steps they need is `require` one of the adapter implementations in their projects `composer.json` and instantiating the HttpClientBundle in their kernel.

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
