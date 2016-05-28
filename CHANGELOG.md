# Change Log


## UNRELEASED

### Added

- Plugin class name container parameters


## 1.1.0 - 2016-05-19

### Added

- Client factories for Buzz.

### Changed

- Guzzle 6 client is now created according to the Httplug specifications with automated minimal behaviour.
  Make sure you configure the Httplug plugins as needed,
  for example if you want to get exceptions for failure HTTP status codes.
- **[BC] PluginClientFactory returns an instance of `Http\Client\Common\PluginClient`** (see [php-http/client-common#14](https://github.com/php-http/client-common/pull/14))
- Plugins are loaded from their new packages


### Fixed

- Puli autoload issue on >=PHP 5.6, see [puli/issues#190](https://github.com/puli/issues/issues/190)


## 1.0.0 - 2016-03-04

### Added

- New configuration for authentication plugin. You may now specify the authentication credentials directly in the bundle's configuration. This will break previous authentication configuration.
- Client factories for Curl, React and Socket.

### Fixed

- Bug with circular reference when a client was named 'default'

### Removed

- Dependency on `php-http/discovery`. You must now install `puli/symfony-bundle` to use auto discovery.


## 0.2.0 - 2016-02-23

## 0.1.0 - 2016-01-11
