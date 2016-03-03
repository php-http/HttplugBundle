<?php

namespace Http\HttplugBundle\Tests\Unit;

use Http\HttplugBundle\ClientFactory\DummyClient;
use Http\HttplugBundle\Collector\MessageJournal;
use Http\HttplugBundle\HttplugFactory;
use Puli\Discovery\Api\Discovery;
use Puli\Discovery\Binding\ClassBinding;
use Webmozart\Expression\Expr;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class HttplugFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testEvaluateConditionString()
    {
        $existingClass = MessageJournal::class;
        $factory = $this->getMockedFactory($existingClass);
        $this->assertInstanceOf(DummyClient::class, $factory->find('type'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testEvaluateConditionInvalidString()
    {
        // String
        $factory = $this->getMockedFactory('non_existent_class');
        $factory->find('type');
    }

    public function testEvaluateConditionCallableTrue()
    {
        $factory = $this->getMockedFactory(
            function () {
                return true;
            }
        );
        $this->assertInstanceOf(DummyClient::class, $factory->find('type'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testEvaluateConditionCallableFalse()
    {
        $factory = $this->getMockedFactory(
            function () {
                return false;
            }
        );

        $factory->find('type');
    }

    public function testEvaluateConditionBooleanTrue()
    {
        $factory = $this->getMockedFactory(true);
        $this->assertInstanceOf(DummyClient::class, $factory->find('type'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testEvaluateConditionBooleanFalse()
    {
        $factory = $this->getMockedFactory(false);
        $factory->find('type');
    }

    public function testEvaluateConditionArrayTrue()
    {
        $factory = $this->getMockedFactory([true, true]);
        $this->assertInstanceOf(DummyClient::class, $factory->find('type'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testEvaluateConditionArrayFalse()
    {
        $factory = $this->getMockedFactory([true, false, true]);
        $factory->find('type');
    }

    public function testEvaluateConditionArrayAssoc()
    {
        $factory = $this->getMockedFactory(['test1' => true, true]);
        $this->assertInstanceOf(DummyClient::class, $factory->find('type'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testEvaluateConditionObject()
    {
        $factory = $this->getMockedFactory(new \DateTime());
        $factory->find('type');
    }

    /**
     * @param $condition
     *
     * @return HttplugFactory
     */
    private function getMockedFactory($condition)
    {
        $discovery = $this->prophesize(Discovery::class);
        $factory = new HttplugFactory($discovery->reveal());
        $binding = $this->prophesize(ClassBinding::class);
        $discovery->findBindings('type', Expr::isInstanceOf('Puli\Discovery\Binding\ClassBinding'))->willReturn(
            [$binding->reveal()]
        );

        $binding->hasParameterValue('depends')->willReturn(true);
        $binding->getClassName()->willReturn(DummyClient::class);
        $binding->getParameterValue('depends')->willReturn($condition);

        return $factory;
    }
}
