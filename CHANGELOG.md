# Change Log

The change log describes what is "Added", "Removed", "Changed" or "Fixed" between each release.

## 1.7.1 - 2017-08-04

### Fixed

- Display of the profiler panel when used in a symfony/flex project.

## 1.7.0 - 2017-07-25

### Added

- Add `Http\Message\MessageFactory`, `Http\Message\StreamFactory`, `Http\Message\UriFactory` and `Http\Client\HttpAsyncClient`
services aliases for autowiring in Symfony 3.3.
- Redirect requests are now displayed as nested.

### Changed

- Plugins are now displayed with their FQCN instead of service id.

### Deprecated

- The `DummyClient` interface.

## 1.6.0 - 2017-05-22

### Added

- Made the "factory" configuration key optional.

### Changed

- We do collect profiler data after the request is processed by a plugin. With this change we 
will for example see the changes of `HeaderAppendPlugin` at that plugin instead of the next one. 

## 1.5.0 - 2017-05-05

### Added

- The real request method and target url are now displayed in the profiler.
- Support the cache plugin configuration for `respect_response_cache_directives`.
- Extended WebProfilerToolbar item to list request with details.
- You can now copy any request as cURL command in the profiler.
- Support for autowring in Symfony 3.3
- Improved configuration validation

### Changed

- The profiler design has been updated.
- Removed stopwatch-plugin in favor of `ProfileClient`.

### Deprecated

- The configuration option `cache.config.respect_cache_headers` should no longer be used. Use `cache.config.respect_response_cache_directives` instead.

## 1.4.1 - 2017-02-23

### Fixed

- Make sure we always have a stack

## 1.4.0 - 2017-02-21

### Changed

- The profiler collector has been rewritten to use objects instead of arrays.
- Bumped minimum Symfony version to 2.8.
- Updated profiler badges to match Symfony 2.8+ profiler design.

### Fixed

- WebProfiler is no longer broken when there was a redirect.
- Dummy client should provide methods of HttpClient and HttpAsyncClient

## 1.3.0 - 2016-08-16

### Added

- You can now also configure client specific plugins in the `plugins` option of a client.
- Some plugins that you previously had to define in your own services can now be configured on the client.
- Support for BatchClient
- The stopwatch plugin included by default when using profiling.

### Changed

- All clients are registered with the PluginClient (even in production)
- Improved debug tool registration

### Deprecated

- `auto` value in `toolbar.enabled` config
- `toolbar` config, use `profiling` instead

### Fixed

- Decoder, Redirect and Retry plugins can now be used and no longer trigger an error because of incorrect constructor arguments.


## 1.2.2 - 2016-07-19

### Fixed

- Do not register debug tools when debugging is disabled (eg. in prod mode)


## 1.2.1 - 2016-07-19

### Fixed

- Auto discovery by using the appropriate discovery class


## 1.2.0 - 2016-07-18

### Added

- Support for cRUL constant names in configuration
- Flexible and HTTP Methods client support
- Discovery strategy to discover configured clients and/or add profiling

### Changed

- Improved collector to include plugin stack in profile data


## 1.1.1 - 2016-06-30

### Changed

- Removed Puli logic and require `php-http/discovery:0.9` which makes Puli optional.


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
