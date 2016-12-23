<?php

namespace Http\HttplugBundle\Collector;

use Http\Client\Exception;
use Http\Message\Formatter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * A data collector for the debug plugin.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class DebugPluginCollector extends DataCollector
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var PluginJournal
     */
    private $journal;

    /**
     * @param Formatter     $formatter
     * @param PluginJournal $journal
     */
    public function __construct(Formatter $formatter, PluginJournal $journal)
    {
        $this->formatter = $formatter;
        $this->journal = $journal;
    }

    /**
     * @param RequestInterface $request
     * @param string           $clientName
     * @param int              $depth
     */
    public function addRequest(RequestInterface $request, $clientName, $depth)
    {
        $this->data[$clientName]['request'][$depth][] = $this->formatter->formatRequest($request);
    }

    /**
     * @param ResponseInterface $response
     * @param string            $clientName
     * @param int               $depth
     */
    public function addResponse(ResponseInterface $response, $clientName, $depth)
    {
        $this->data[$clientName]['response'][$depth][] = $this->formatter->formatResponse($response);
        $this->data[$clientName]['failure'][$depth][] = false;
    }

    /**
     * @param Exception $exception
     * @param string    $clientName
     * @param int       $depth
     */
    public function addFailure(Exception $exception, $clientName, $depth)
    {
        if ($exception instanceof Exception\HttpException) {
            $formattedResponse = $this->formatter->formatResponse($exception->getResponse());
        } elseif ($exception instanceof Exception\TransferException) {
            $formattedResponse = $exception->getMessage();
        } else {
            $formattedResponse = sprintf('Unexpected exception of type "%s"', get_class($exception));
        }

        $this->data[$clientName]['response'][$depth][] = $formattedResponse;
        $this->data[$clientName]['failure'][$depth][] = true;
    }

    /**
     * Returns the successful request-response pairs.
     *
     * @return int
     */
    public function getSuccessfulRequests()
    {
        $count = 0;
        foreach ($this->data as $client) {
            if (isset($client['failure'])) {
                foreach ($client['failure'][0] as $failure) {
                    if (!$failure) {
                        ++$count;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Returns the failed request-response pairs.
     *
     * @return int
     */
    public function getFailedRequests()
    {
        $count = 0;
        foreach ($this->data as $client) {
            if (isset($client['failure'])) {
                foreach ($client['failure'][0] as $failure) {
                    if ($failure) {
                        ++$count;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Returns the total number of request made.
     *
     * @return int
     */
    public function getTotalRequests()
    {
        return $this->getSuccessfulRequests() + $this->getFailedRequests();
    }

    /**
     * Return a RequestStackProvider for each client.
     *
     * @return RequestStackProvider[]
     */
    public function getClients()
    {
        return RequestStackProvider::createFromCollectedData($this->data);
    }

    /**
     * @return PluginJournal
     */
    public function getJournal()
    {
        return $this->journal;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
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
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->data, $this->journal]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($data)
    {
        list($this->data, $this->journal) = unserialize($data);
    }
}
