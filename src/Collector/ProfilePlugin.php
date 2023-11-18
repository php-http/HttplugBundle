<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Collector;

use Exception;
use Http\Client\Common\Plugin;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The ProfilePlugin decorates any Plugin to fill Profile to keep representation of plugin input/output. Created profile
 * is pushed in the current Stack.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
final class ProfilePlugin implements Plugin
{
    use Plugin\VersionBridgePlugin;

    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var Formatter
     */
    private $formatter;

    public function __construct(Plugin $plugin, Collector $collector, Formatter $formatter)
    {
        $this->plugin = $plugin;
        $this->collector = $collector;
        $this->formatter = $formatter;
    }

    protected function doHandleRequest(RequestInterface $request, callable $next, callable $first)
    {
        $profile = new Profile(get_class($this->plugin));

        $stack = $this->collector->getActiveStack();
        $stack->addProfile($profile);

        // wrap the next callback to profile the plugin request changes
        $wrappedNext = function (RequestInterface $request) use ($next, $profile) {
            $this->onOutgoingRequest($request, $profile);

            return $next($request);
        };

        // wrap the first callback to profile the plugin request changes
        $wrappedFirst = function (RequestInterface $request) use ($first, $profile) {
            $this->onOutgoingRequest($request, $profile);

            return $first($request);
        };

        try {
            $promise = $this->plugin->handleRequest($request, $wrappedNext, $wrappedFirst);
        } catch (Exception $e) {
            $this->onException($request, $profile, $e, $stack);

            throw $e;
        }

        return $promise->then(function (ResponseInterface $response) use ($profile, $request, $stack) {
            $this->onOutgoingResponse($response, $profile, $request, $stack);

            return $response;
        }, function (Exception $exception) use ($profile, $request, $stack) {
            $this->onException($request, $profile, $exception, $stack);

            throw $exception;
        });
    }

    /**
     * @param Stack $stack
     */
    private function onException(
        RequestInterface $request,
        Profile $profile,
        Exception $exception,
        Stack $stack = null
    ) {
        $profile->setFailed(true);
        $profile->setResponse($this->formatter->formatException($exception));
        $this->collectRequestInformation($request, $stack);
    }

    private function onOutgoingRequest(RequestInterface $request, Profile $profile)
    {
        $profile->setRequest($this->formatter->formatRequest($request));
    }

    /**
     * @param Stack $stack
     */
    private function onOutgoingResponse(ResponseInterface $response, Profile $profile, RequestInterface $request, Stack $stack = null)
    {
        $profile->setResponse($this->formatter->formatResponse($response));
        $this->collectRequestInformation($request, $stack);
    }

    /**
     * Collect request information when not already done by the HTTP client. This happens when using the CachePlugin
     * and the cache is hit without re-validation.
     */
    private function collectRequestInformation(RequestInterface $request, Stack $stack = null)
    {
        $uri = $request->getUri();
        if (empty($stack->getRequestTarget())) {
            $stack->setRequestTarget($request->getRequestTarget());
        }
        if (empty($stack->getRequestMethod())) {
            $stack->setRequestMethod($request->getMethod());
        }
        if (empty($stack->getRequestScheme())) {
            $stack->setRequestScheme($uri->getScheme());
        }
        if (empty($stack->getRequestPort())) {
            $stack->setRequestPort($uri->getPort());
        }
        if (empty($stack->getRequestHost())) {
            $stack->setRequestHost($uri->getHost());
        }
        if (empty($stack->getClientRequest())) {
            $stack->setClientRequest($this->formatter->formatRequest($request));
        }
        if (empty($stack->getCurlCommand())) {
            $stack->setCurlCommand($this->formatter->formatAsCurlCommand($request));
        }
    }
}
