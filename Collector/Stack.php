<?php

namespace Http\HttplugBundle\Collector;

/**
 * A Stack hold a collection of Profile to track the whole request execution.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
final class Stack
{
    /**
     * @var string
     */
    private $client;

    /**
     * @var Profile[]
     */
    private $profiles = [];

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
     * @var string
     */
    private $requestTarget;

    /**
     * @var string
     */
    private $requestMethod;

    /**
     * @param string $client
     * @param string $request
     */
    public function __construct($client, $request)
    {
        $this->client = $client;
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Profile $profile
     */
    public function addProfile(Profile $profile)
    {
        $this->profiles[] = $profile;
    }

    /**
     * @return Profile[]
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
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

    /**
     * @return string
     */
    public function getRequestTarget()
    {
        return $this->requestTarget;
    }

    /**
     * @param string $requestTarget
     */
    public function setRequestTarget($requestTarget)
    {
        $this->requestTarget = $requestTarget;
    }

    /**
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * @param string $requestMethod
     */
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;
    }
}
