<?php

namespace Http\HttplugBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfilerTest extends WebTestCase
{
    public function testShowProfiler()
    {
        $client = static::createClient();

        //Browse any page to get a profile
        $client->request('GET', '/');

        $crawler = $client->request('GET', '/_profiler/latest?panel=httplug');
        $title = $crawler->filterXPath('//*[@id="collector-content"]/h2');

        $this->assertCount(1, $title);
        $this->assertEquals('HTTPlug', $title->html());
    }
}
