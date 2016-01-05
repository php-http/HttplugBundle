<?php

namespace Http\HttplugBundle\Collector;

use Http\Client\Exception;
use Http\Client\Plugin\Journal;
use Http\Message\Formatter;
use Http\Message\Formatter\SimpleFormatter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class MessageJournal extends DataCollector implements Journal
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @param Formatter $formatter
     */
    public function __construct(Formatter $formatter = null)
    {
        $this->formatter = $formatter ?: new SimpleFormatter();
        $this->data = ['success' => [], 'failure' => []];
    }

    /**
     * {@inheritdoc}
     */
    public function addSuccess(RequestInterface $request, ResponseInterface $response)
    {
        $this->data['success'][] = [
            'request'  => $this->formatter->formatRequest($request),
            'response' => $this->formatter->formatResponse($response),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function addFailure(RequestInterface $request, Exception $exception)
    {
        if ($exception instanceof Exception\HttpException) {
            $formattedResponse = $this->formatter->formatResponse($exception->getResponse());
        } elseif ($exception instanceof Exception\TransferException) {
            $formattedResponse = $exception->getMessage();
        } else {
            $formattedResponse = sprintf('Unexpected exception of type "%s"', get_class($exception));
        }

        $this->data['failure'][] = [
            'request'  => $this->formatter->formatRequest($request),
            'response' => $formattedResponse,
        ];
    }

    /**
     * Get the successful request-resonse pairs.
     *
     * @return array
     */
    public function getSucessfulRequests()
    {
        return $this->data['success'];
    }

    /**
     * Get the failed request-resonse pairs.
     *
     * @return array
     */
    public function getFailedRequests()
    {
        return $this->data['failure'];
    }

    /**
     * Get the total number of request made.
     *
     * @return int
     */
    public function getTotalRequests()
    {
        return count($this->data['success']) + count($this->data['failure']);
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        // We do not need to collect any data form the Symfony Request and Response
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'httplug';
    }
}
