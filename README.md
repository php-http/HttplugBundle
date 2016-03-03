# HTTPlug Bundle

[![Latest Version](https://img.shields.io/github/release/php-http/HttplugBundle.svg?style=flat-square)](https://github.com/php-http/HttplugBundle/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/php-http/HttplugBundle.svg?style=flat-square)](https://travis-ci.org/php-http/HttplugBundle)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/php-http/HttplugBundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/php-http/HttplugBundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/php-http/HttplugBundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/php-http/HttplugBundle)
[![Total Downloads](https://img.shields.io/packagist/dt/php-http/httplug-bundle.svg?style=flat-square)](https://packagist.org/packages/php-http/HttplugBundle)

**Symfony integration for [HTTPlug](http://httplug.io/).**


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

## Documentation

Please see the [official documentation](http://docs.php-http.org/en/latest/integrations/symfony-bundle.html).


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
