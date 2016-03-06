# Change Log

## UNRELEASED

### Added

- Client factories for Buzz.


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
