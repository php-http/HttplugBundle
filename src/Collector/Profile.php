<?php

namespace Http\HttplugBundle\Collector;

/**
 * A Profile holds representation of what goes in an plugin (request) and what goes out (response and failure state).
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
final class Profile
{
    /**
     * @var string
     */
    private $plugin;

    /**
     * @var string
     */
    private $request;

    /**
     * @var string
     */
    private $response;

    /**
     * @var bool
     */
    private $failed = false;

    /**
     * @param string $plugin
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @return string
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param string $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return $this->failed;
    }

    /**
     * @param bool $failed
     */
    public function setFailed($failed)
    {
        $this->failed = $failed;
    }
}
