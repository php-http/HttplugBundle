<?php

namespace Http\HttplugBundle\Collector;

/**
 * An object that managed collected data for each client. This is used to display data.
 *
 * The Request object at $requests[0][2] is the state of the object between the third
 * and the fourth plugin. The response after that plugin is found in $responses[0][2].
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class RequestStackProvider
{
    /**
     * Array that tell if a request errored or not. true = success, false = failure.
     *
     * @var array
     */
    private $failures;

    /**
     * A multidimensional array with requests.
     * $requests[0][0] is the first request before all plugins.
     * $requests[0][1] is the first request after the first plugin.
     *
     * @var array
     */
    private $requests;

    /**
     * A multidimensional array with responses.
     * $responses[0][0] is the first responses before all plugins.
     * $responses[0][1] is the first responses after the first plugin.
     *
     * @var array
     */
    private $responses;

    /**
     * @param array $failures  if the response was successful or not
     * @param array $requests
     * @param array $responses
     */
    public function __construct(array $failures, array $requests, array $responses)
    {
        $this->failures = $failures;
        $this->requests = $requests;
        $this->responses = $responses;
    }

    /**
     * Create an array of ClientDataCollector from collected data.
     *
     * @param array $data
     *
     * @return RequestStackProvider[]
     */
    public static function createFromCollectedData(array $data)
    {
        $clientData = [];
        foreach ($data as $clientName => $messages) {
            $clientData[$clientName] = static::createOne($messages);
        }

        return $clientData;
    }

    /**
     * @param array $messages is an array with keys 'failure', 'request' and 'response' which hold requests for each call to
     *                        sendRequest and for each depth
     *
     * @return RequestStackProvider
     */
    private static function createOne($messages)
    {
        $orderedFaulure = [];
        $orderedRequests = [];
        $orderedResponses = [];

        foreach ($messages['failure'] as $depth => $failures) {
            foreach ($failures as $idx => $failure) {
                $orderedFaulure[$idx][$depth] = $failure;
            }
        }

        foreach ($messages['request'] as $depth => $requests) {
            foreach ($requests as $idx => $request) {
                $orderedRequests[$idx][$depth] = $request;
            }
        }

        foreach ($messages['response'] as $depth => $responses) {
            foreach ($responses as $idx => $response) {
                $orderedResponses[$idx][$depth] = $response;
            }
        }

        return new self($orderedFaulure, $orderedRequests, $orderedResponses);
    }

    /**
     * Get the index keys for the request and response stacks.
     *
     * @return array
     */
    public function getStackIndexKeys()
    {
        return array_keys($this->requests);
    }

    /**
     * @param int $idx
     *
     * @return array
     */
    public function getRequstStack($idx)
    {
        return $this->requests[$idx];
    }

    /**
     * @param int $idx
     *
     * @return array
     */
    public function getResponseStack($idx)
    {
        return $this->responses[$idx];
    }

    /**
     * @param int $idx
     *
     * @return array
     */
    public function getFailureStack($idx)
    {
        return $this->failures[$idx];
    }
}
