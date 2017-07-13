<?php

namespace Http\HttplugBundle\Tests\Unit\Discovery;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\HttplugBundle\Discovery\ConfiguredClientsStrategy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ConfiguredClientsStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCandidates()
    {
        $httpClient = $this->getMockBuilder(HttpClient::class)->getMock();
        $httpAsyncClient = $this->getMockBuilder(HttpAsyncClient::class)->getMock();
        $locator = $this->getLocatorMock();
        $locator
            ->expects($this->exactly(2))
            ->method('has')
            ->willReturn(true)
        ;
        $locator
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->onConsecutiveCalls($httpClient, $httpAsyncClient))
        ;

        $strategy = new ConfiguredClientsStrategy($locator, 'httplug.auto_discovery.auto_discovered_client', 'httplug.auto_discovery.auto_discovered_async');

        $candidates = $strategy::getCandidates(HttpClient::class);
        $candidate = array_shift($candidates);
        $this->assertEquals($httpClient, $candidate['class']());

        $candidates = $strategy::getCandidates(HttpAsyncClient::class);
        $candidate = array_shift($candidates);
        $this->assertEquals($httpAsyncClient, $candidate['class']());
    }

    public function testGetCandidatesEmpty()
    {
        $locator = $this->getLocatorMock();
        $locator
            ->expects($this->exactly(2))
            ->method('has')
            ->willReturn(false)
        ;
        $locator
            ->expects($this->never())
            ->method('get')
        ;

        $strategy = new ConfiguredClientsStrategy($locator, 'httplug.auto_discovery.auto_discovered_client', 'httplug.auto_discovery.auto_discovered_async');

        $candidates = $strategy::getCandidates(HttpClient::class);
        $this->assertEquals([], $candidates);

        $candidates = $strategy::getCandidates(HttpAsyncClient::class);
        $this->assertEquals([], $candidates);
    }

    public function testGetCandidatesEmptyAsync()
    {
        $httpClient = $this->getMockBuilder(HttpClient::class)->getMock();

        $locator = $this->getLocatorMock();
        $locator
            ->expects($this->exactly(2))
            ->method('has')
            ->willReturnMap([
                ['httplug.auto_discovery.auto_discovered_client', true],
                ['httplug.auto_discovery.auto_discovered_async', false],
            ])
        ;
        $locator
            ->expects($this->once())
            ->method('get')
            ->willReturn($httpClient)
        ;

        $strategy = new ConfiguredClientsStrategy($locator, 'httplug.auto_discovery.auto_discovered_client', 'httplug.auto_discovery.auto_discovered_async');

        $candidates = $strategy::getCandidates(HttpClient::class);
        $candidate = array_shift($candidates);
        $this->assertEquals($httpClient, $candidate['class']());

        $candidates = $strategy::getCandidates(HttpAsyncClient::class);
        $this->assertEquals([], $candidates);
    }

    public function testGetCandidatesEmptySync()
    {
        $httpAsyncClient = $this->getMockBuilder(HttpAsyncClient::class)->getMock();

        $locator = $this->getLocatorMock();
        $locator
            ->expects($this->exactly(2))
            ->method('has')
            ->willReturnMap([
                ['httplug.auto_discovery.auto_discovered_client', false],
                ['httplug.auto_discovery.auto_discovered_async', true],
            ])
        ;
        $locator
            ->expects($this->once())
            ->method('get')
            ->willReturn($httpAsyncClient)
        ;

        $strategy = new ConfiguredClientsStrategy($locator, 'httplug.auto_discovery.auto_discovered_client', 'httplug.auto_discovery.auto_discovered_async');

        $candidates = $strategy::getCandidates(HttpClient::class);
        $this->assertEquals([], $candidates);

        $candidates = $strategy::getCandidates(HttpAsyncClient::class);
        $candidate = array_shift($candidates);
        $this->assertEquals($httpAsyncClient, $candidate['class']());
    }

    /**
     * @return ContainerInterface|ServiceLocator
     */
    private function getLocatorMock()
    {
        if (class_exists(ServiceLocator::class)) {
            return $this->getMockBuilder(ServiceLocator::class)->disableOriginalConstructor()->getMock();
        }

        return $this->getMockBuilder(ContainerInterface::class)->getMock();
    }
}
