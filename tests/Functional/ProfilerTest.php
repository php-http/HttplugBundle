<?php

namespace Http\HttplugBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfilerTest extends WebTestCase
{
    /**
     * @group legacy
     */
    public function testShowProfiler(): void
    {
        $client = static::createClient();

        //Browse any page to get a profile
        $client->request('GET', '/');

        $client->request('GET', '/_profiler/latest?panel=httplug');
        $content = $client->getResponse()->getContent();
        $this->assertTrue(false !== strpos($content, '<h2>HTTPlug</h2>'));
    }
}
