<?php

namespace Http\HttplugBundle\Tests\Unit\DependencyInjection;

use Http\HttplugBundle\DependencyInjection\Configuration;
use Http\HttplugBundle\DependencyInjection\HttplugExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;

/**
 * @author David Buchmann <mail@davidbu.ch>
 */
class ConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    protected function getContainerExtension()
    {
        return new HttplugExtension();
    }

    protected function getConfiguration()
    {
        return new Configuration();
    }

    public function testEmptyConfiguration()
    {
        $expectedConfiguration = array(
            'main_alias' => array(
                'client' => 'httplug.client.default',
                'message_factory' => 'httplug.message_factory.default',
                'uri_factory' => 'httplug.uri_factory.default',
            ),
            'classes' => array(
                'client' => null,
                'message_factory' => null,
                'uri_factory' => null,
            ),
        );

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, array(
            'config/empty.yml',
            'config/empty.xml',
            'config/empty.php',
        ));

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, array($format));
        }
    }

    public function testSupportsAllConfigFormats()
    {
        $expectedConfiguration = array(
            'main_alias' => array(
                'client' => 'my_client',
                'message_factory' => 'my_message_factory',
                'uri_factory' => 'my_uri_factory',
            ),
            'classes' => array(
                'client' => 'Http\Adapter\Guzzle6HttpAdapter',
                'message_factory' => 'Http\Discovery\MessageFactory\GuzzleFactory',
                'uri_factory' => 'Http\Discovery\UriFactory\GuzzleFactory',
            ),
        );

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, array(
            'config/full.yml',
            'config/full.xml',
            'config/full.php',
        ));

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, array($format));
        }
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Nonexisting\Class
     */
    public function testMissingClass()
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/invalid.yml';
        $this->assertProcessedConfigurationEquals(array(), array($file));
    }
}
