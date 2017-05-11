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
        $profile = new Profile($this->pluginName, $this->formatter->formatRequest($request));

        if (null !== $stack = $this->collector->getCurrentStack()) {
            $stack->addProfile($profile);
        }

        return $this->plugin->handleRequest($request, $next, $first)->then(function (ResponseInterface $response) use ($profile) {
            $profile->setResponse($this->formatter->formatResponse($response));

            return $response;
        }, function (Exception $exception) use ($profile) {
            $profile->setFailed(true);
            $profile->setResponse($this->formatter->formatException($exception));

            throw $exception;
        });
    }
}
