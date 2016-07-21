<?php

namespace Http\HttplugBundle\Tests\Unit\Discovery;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\HttplugBundle\Discovery\ConfiguredClientsStrategy;

class ConfiguredClientsStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCandidates()
    {
        $httpClient = $this->getMock(HttpClient::class);
        $httpAsyncClient = $this->getMock(HttpAsyncClient::class);
        $strategy  = new ConfiguredClientsStrategy($httpClient, $httpAsyncClient);

        $candidates = $strategy::getCandidates(HttpClient::class);
        $candidate = array_shift($candidates);
        $this->assertEquals($httpClient, $candidate['class']());

        $candidates = $strategy::getCandidates(HttpAsyncClient::class);
        $candidate = array_shift($candidates);
        $this->assertEquals($httpAsyncClient, $candidate['class']());
    }

    public function testGetCandidatesEmpty()
    {
        $strategy  = new ConfiguredClientsStrategy(null, null);

        $candidates = $strategy::getCandidates(HttpClient::class);
        $this->assertEquals([], $candidates);

        $candidates = $strategy::getCandidates(HttpAsyncClient::class);
        $this->assertEquals([], $candidates);
    }

    public function testGetCandidatesEmptyAsync()
    {
        $httpClient = $this->getMock(HttpClient::class);
        $strategy  = new ConfiguredClientsStrategy($httpClient, null);

        $candidates = $strategy::getCandidates(HttpClient::class);
        $candidate = array_shift($candidates);
        $this->assertEquals($httpClient, $candidate['class']());

        $candidates = $strategy::getCandidates(HttpAsyncClient::class);
        $this->assertEquals([], $candidates);
    }


    public function testGetCandidatesEmptySync()
    {
        $httpAsyncClient = $this->getMock(HttpAsyncClient::class);
        $strategy  = new ConfiguredClientsStrategy(null, $httpAsyncClient);

        $candidates = $strategy::getCandidates(HttpClient::class);
        $this->assertEquals([], $candidates);

        $candidates = $strategy::getCandidates(HttpAsyncClient::class);
        $candidate = array_shift($candidates);
        $this->assertEquals($httpAsyncClient, $candidate['class']());
    }
}
