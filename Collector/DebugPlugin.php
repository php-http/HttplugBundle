<?php

namespace Http\HttplugBundle\Collector;

use Http\Client\Common\Plugin;
use Http\Client\Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A plugin used for log requests and responses. This plugin is executed between each normal plugin.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class DebugPlugin implements Plugin
{
    /**
     * @var DebugPluginCollector
     */
    private $collector;

    /**
     * @var string
     */
    private $clientName;

    /**
     * @var int
     */
    private $depth = -1;

    /**
     * @param DebugPluginCollector $collector
     * @param string               $clientName
     */
    public function __construct(DebugPluginCollector $collector, $clientName)
    {
        $this->collector = $collector;
        $this->clientName = $clientName;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first)
    {
        $collector = $this->collector;
        $clientName = $this->clientName;
        $depth = &$this->depth;

        $collector->addRequest($request, $clientName, ++$depth);

        return $next($request)->then(function (ResponseInterface $response) use ($collector, $clientName, &$depth) {
            $collector->addResponse($response, $clientName, $depth--);

            return $response;
        }, function (Exception $exception) use ($collector, $clientName, &$depth) {
            $collector->addFailure($exception, $clientName, $depth--);

            throw $exception;
        });
    }
}
