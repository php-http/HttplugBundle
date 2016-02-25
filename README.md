# HttplugBundle

[![Latest Version](https://img.shields.io/github/release/php-http/HttplugBundle.svg?style=flat-square)](https://github.com/php-http/HttplugBundle/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/php-http/HttplugBundle.svg?style=flat-square)](https://travis-ci.org/php-http/HttplugBundle)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/php-http/httplug-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/php-http/HttplugBundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/php-http/httplug-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/php-http/HttplugBundle)
[![Total Downloads](https://img.shields.io/packagist/dt/php-http/httplug-bundle.svg?style=flat-square)](https://packagist.org/packages/php-http/HttplugBundle)

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

For information how to write applications with the services provided by this bundle, have a look at the [Httplug documentation](http://docs.php-http.org).


### Use in Applications

#### Custom services

| Service id | Description |
| ---------- | ----------- |
| httplug.message_factory | Service* that provides the `Http\Message\MessageFactory`
| httplug.uri_factory | Service* that provides the `Http\Message\UriFactory`
| httplug.stream_factory | Service* that provides the `Http\Message\StreamFactory`
| httplug.client.[name] | This is your Httpclient that you have configured. With the configuration below the name would be `acme_client`.
| httplug.client | This is the first client configured or a client named `default`.
| httplug.plugin.content_length <br> httplug.plugin.decoder<br> httplug.plugin.error<br> httplug.plugin.logger<br> httplug.plugin.redirect<br> httplug.plugin.retry<br> httplug.plugin.stopwatch | These are plugins that are enabled by default. These services are not public and may only be used when configure HttpClients or other services.
| httplug.plugin.cache<br> httplug.plugin.cookie<br> httplug.plugin.history | These are plugins that are disabled by default. They need to be configured before they can be used. These services are not public and may only be used when configure HttpClients or other services.

\* *These services are always an alias to another service. You can specify your own service or leave the default, which is the same name with `.default` appended. The default services in turn use the service discovery mechanism to provide the best available implementation. You can specify a class for each of the default services to use instead of discovery, as long as those classes can be instantiated without arguments.*


If you need a more custom setup, define the services in your application configuration and specify your service in the `main_alias` section. For example, to add authentication headers, you could define a service that decorates the service `httplug.client.default` with a plugin that injects the authentication headers into the request and configure `httplug.main_alias.client` to the name of your service.

```yaml
httplug:
    clients:
        acme_client: # This is the name of the client
        factory: 'httplug.factory.guzzle6'

    main_alias:
        client: httplug.client.default
        message_factory: httplug.message_factory.default
        uri_factory: httplug.uri_factory.default
        stream_factory: httplug.stream_factory.default
    classes:
        # uses discovery if not specified
        client: ~
        message_factory: ~
        uri_factory: ~
        stream_factory: ~
```


#### Configure your client

You can configure your clients with some good default options. The clients are later registered as services.

```yaml
httplug:
    clients:
        my_guzzle5:
            factory: 'httplug.factory.guzzle5'
            config:
                # These options are given to Guzzle without validation.
                defaults:
                    base_uri: 'http://google.se/'
                    verify_ssl: false
                    timeout: 4
                    headers:
                        Content-Type: 'application/json'
        acme:
            factory: 'httplug.factory.guzzle6'
            config:
                base_uri: 'http://google.se/'

```

```php

$httpClient = $this->container->get('httplug.client.my_guzzle5');
$httpClient = $this->container->get('httplug.client.acme');
```


#### Plugins

You can configure the clients with plugins.

```yaml
// services.yml
acme_plugin:
      class: Acme\Plugin\MyCustonPlugin
      arguments: ["%api_key%"]
```
```yaml
// config.yml
httpug:
    plugins:
        cache:
            cache_pool: 'my_cache_pool'
    clients:
        acme:
            factory: 'httplug.factory.guzzle6'
            plugins: ['acme_plugin', 'httplug.plugin.cache', ''httplug.plugin.retry']
            config:
                base_uri: 'http://google.se/'
```

#### Authentication

```yaml
// config.yml
httpug:
    plugins:
        authentication:
            my_basic:
                type: 'basic'
                username: 'my_username'
                password: 'p4ssw0rd'
            my_wsse:
                type: 'wsse'
                username: 'my_username'
                password: 'p4ssw0rd'
            my_brearer:
                type: 'bearer'
                token: 'authentication_token_hash'
            my_service:
                type: 'service'
                service: 'my_authentication_service'

    clients:
        acme:
            factory: 'httplug.factory.guzzle6'
            plugins: ['httplug.plugin.authentication.my_wsse']
```


### Use for Reusable Bundles

Rather than code against specific HTTP clients, you want to use the Httplug `Client` interface. To avoid building your own infrastructure to define services for the client, simply `require: php-http/httplug-bundle` in your bundles `composer.json`. You SHOULD provide configuration for each of your services that needs an HTTP client to specify the service to use, defaulting to `httplug.client`. This way, the default case needs no additional configuration for your users.

The only steps they need is `require` one of the adapter implementations in their projects `composer.json` and instantiating the HttplugBundle in their kernel.


## Testing

``` bash
$ composer test
```


## Contributing

Please see our [contributing guide](http://docs.php-http.org/en/latest/development/contributing.html).


## Security

If you discover any security related issues, please contact us at [security@php-http.org](mailto:security@php-http.org).


## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
