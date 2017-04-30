<?php

namespace Http\HttplugBundle\Collector;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * The Collector hold profiled Stacks pushed by StackPlugin. It also have a list of configured clients.
 * All those data are used to display the HTTPlug panel in the Symfony profiler.
 *
 * The collector is not designed for execution in a threaded application and does not support plugins that execute an
 * other request before the current one is sent by the client.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
class Collector extends DataCollector
{
    /**
     * @param array $clients
     */
    public function __construct(array $clients)
    {
        $this->data['stacks'] = [];
        $this->data['clients'] = $clients;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, Exception $exception = null)
    {
        // We do not need to collect any data from the Symfony Request and Response
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'httplug';
    }

    /**
     * @param Stack $stack
     */
    public function addStack(Stack $stack)
    {
        $this->data['stacks'][] = $stack;
    }

    /**
     * @return Stack[]
     */
    public function getStacks()
    {
        return $this->data['stacks'];
    }

    /**
     * @return Stack|bool false if no current stack.
     */
    public function getCurrentStack()
    {
        return end($this->data['stacks']);
    }

    /**
     * @return Stack[]
     */
    public function getSuccessfulStacks()
    {
        return array_filter($this->data['stacks'], function (Stack $stack) {
            return !$stack->isFailed();
        });
    }

    /**
     * @return Stack[]
     */
    public function getFailedStacks()
    {
        return array_filter($this->data['stacks'], function (Stack $stack) {
            return $stack->isFailed();
        });
    }

    /**
     * @return array
     */
    public function getClients()
    {
        return $this->data['clients'];
    }

    /**
     * @param $client
     *
     * @return Stack[]
     */
    public function getClientStacks($client)
    {
        return array_filter($this->data['stacks'], function (Stack $stack) use ($client) {
            return $stack->getClient() == $client;
        });
    }

    /**
     * @return int
     */
    public function getTotalDuration()
    {
        return array_reduce($this->data['stacks'], function ($carry, Stack $stack) {
            return $carry + $stack->getDuration();
        }, 0);
    }
}
