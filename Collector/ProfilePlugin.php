<?php

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
class ProfilePlugin implements Plugin
{
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

    /**
     * @var string
     */
    private $pluginName;

    /**
     * @param Plugin    $plugin
     * @param Collector $collector
     * @param Formatter $formatter
     * @param string    $pluginName
     */
    public function __construct(Plugin $plugin, Collector $collector, Formatter $formatter, $pluginName)
    {
        $this->plugin = $plugin;
        $this->collector = $collector;
        $this->formatter = $formatter;
        $this->pluginName = $pluginName;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first)
    {
        $profile = new Profile($this->pluginName);

        $stack = $this->collector->getCurrentStack();
        if (null !== $stack) {
            $stack->addProfile($profile);
        }

        // wrap the next callback to profile the plugin request changes
        $wrappedNext = function (RequestInterface $request) use ($next, $profile) {
            $profile->setRequest($this->formatter->formatRequest($request));

            return $next($request);
        };

        // wrap the first callback to profile the plugin request changes
        $wrappedFirst = function (RequestInterface $request) use ($first, $profile) {
            $profile->setRequest($this->formatter->formatRequest($request));

            return $first($request);
        };

        try {
            $promise = $this->plugin->handleRequest($request, $wrappedNext, $wrappedFirst);
        } catch (Exception $e) {
            $profile->setFailed(true);
            $profile->setResponse($this->formatter->formatException($e));
            $this->collectRequestInformation($request, $stack);

            throw $e;
        }

        return $promise->then(function (ResponseInterface $response) use ($profile, $request, $stack) {
            $profile->setResponse($this->formatter->formatResponse($response));
            $this->collectRequestInformation($request, $stack);

            return $response;
        }, function (Exception $exception) use ($profile, $request, $stack) {
            $profile->setFailed(true);
            $profile->setResponse($this->formatter->formatException($exception));
            $this->collectRequestInformation($request, $stack);

            throw $exception;
        });
    }

    /**
     * Collect request information when not already done by the HTTP client. This happens when using the CachePlugin
     * and the cache is hit without re-validation.
     *
     * @param RequestInterface $request
     * @param Stack|null       $stack
     */
    private function collectRequestInformation(RequestInterface $request, Stack $stack = null)
    {
        if (null === $stack) {
            return;
        }

        if (empty($stack->getRequestTarget())) {
            $stack->setRequestTarget($request->getRequestTarget());
        }
        if (empty($stack->getRequestMethod())) {
            $stack->setRequestMethod($request->getMethod());
        }
        if (empty($stack->getRequestScheme())) {
            $stack->setRequestScheme($request->getUri()->getScheme());
        }
        if (empty($stack->getRequestHost())) {
            $stack->setRequestHost($request->getUri()->getHost());
        }
        if (empty($stack->getClientRequest())) {
            $stack->setClientRequest($this->formatter->formatRequest($request));
        }
        if (empty($stack->getCurlCommand())) {
            $stack->setCurlCommand($this->formatter->formatAsCurlCommand($request));
        }
    }
}
