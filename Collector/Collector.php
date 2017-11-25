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
     * @var Stack|null
     */
    private $activeStack;

    public function __construct()
    {
        $this->reset();
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
    public function reset()
    {
        $this->data['stacks'] = [];
        $this->activeStack = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'httplug';
    }

    /**
     * Mark the stack as active. If a stack was already active, use it as parent for our stack.
     *
     * @param Stack $stack
     */
    public function activateStack(Stack $stack)
    {
        if (null !== $this->activeStack) {
            $stack->setParent($this->activeStack);
        }

        $this->activeStack = $stack;
    }

    /**
     * Mark the stack as inactive.
     *
     * @param Stack $stack
     */
    public function deactivateStack(Stack $stack)
    {
        $this->activeStack = $stack->getParent();
    }

    /**
     * @return Stack|null
     */
    public function getActiveStack()
    {
        return $this->activeStack;
    }

    /**
     * @param Stack $stack
     */
    public function addStack(Stack $stack)
    {
        $this->data['stacks'][] = $stack;
    }

    /**
     * @param Stack $parent
     *
     * @return Stack[]
     */
    public function getChildrenStacks(Stack $parent)
    {
        return array_filter($this->data['stacks'], function (Stack $stack) use ($parent) {
            return $stack->getParent() === $parent;
        });
    }

    /**
     * @return Stack[]
     */
    public function getStacks()
    {
        return $this->data['stacks'];
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
        $stacks = array_filter($this->data['stacks'], function (Stack $stack) {
            return null === $stack->getParent();
        });

        return array_unique(array_map(function (Stack $stack) {
            return $stack->getClient();
        }, $stacks));
    }

    /**
     * @param $client
     *
     * @return Stack[]
     */
    public function getClientRootStacks($client)
    {
        return array_filter($this->data['stacks'], function (Stack $stack) use ($client) {
            return $stack->getClient() == $client && null == $stack->getParent();
        });
    }

    /**
     * Count all messages for a client.
     *
     * @param $client
     *
     * @return int
     */
    public function countClientMessages($client)
    {
        return array_sum(array_map(function (Stack $stack) {
            return $this->countStackMessages($stack);
        }, $this->getClientRootStacks($client)));
    }

    /**
     * Recursively count message in stack.
     *
     * @param Stack $stack
     *
     * @return int
     */
    private function countStackMessages(Stack $stack)
    {
        return 1 + array_sum(array_map(function (Stack $child) {
            return $this->countStackMessages($child);
        }, $this->getChildrenStacks($stack)));
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
