<?php

namespace Http\HttplugBundle;

use Puli\Discovery\Api\Discovery;
use Puli\Discovery\Binding\ClassBinding;
use Webmozart\Expression\Expr;

/**
 * This class is a wrapper around Puli discovery.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class HttplugFactory
{
    /**
     * @var Discovery
     */
    private $discovery;

    /**
     * @param Discovery $discovery
     */
    public function __construct(Discovery $discovery)
    {
        $this->discovery = $discovery;
    }

    /**
     * Creates a class of type.
     *
     * @param string $type
     *
     * @return object
     */
    public function find($type)
    {
        $class = $this->findOneByType($type);

        // TODO: use doctrine instantiator?
        return new $class();
    }

    /**
     * Finds a class of type and resolves optional dependency conditions.
     *
     * @param string $type
     *
     * @return string
     *
     * @throws \RuntimeException if no class is found.
     */
    private function findOneByType($type)
    {
        /** @var ClassBinding[] $bindings */
        $bindings = $this->discovery->findBindings(
            $type,
            Expr::isInstanceOf('Puli\Discovery\Binding\ClassBinding')
        );

        foreach ($bindings as $binding) {
            if ($binding->hasParameterValue('depends')) {
                $dependency = $binding->getParameterValue('depends');

                if (false === $this->evaluateCondition($dependency)) {
                    continue;
                }
            }

            return $binding->getClassName();
        }

        throw new \RuntimeException(sprintf('Class binding of type "%s" is not found', $type));
    }

    /**
     * Evaulates conditions to boolean.
     *
     * @param mixed $condition
     *
     * @return bool
     *
     * TODO: review this method
     */
    protected function evaluateCondition($condition)
    {
        if (is_string($condition)) {
            // Should be extended for functions, extensions???
            return class_exists($condition);
        } elseif (is_callable($condition)) {
            return $condition();
        } elseif (is_bool($condition)) {
            return $condition;
        } elseif (is_array($condition)) {
            $evaluatedCondition = true;

            // Immediately stop execution if the condition is false
            for ($i = 0; $i < count($condition) && false !== $evaluatedCondition; ++$i) {
                $evaluatedCondition &= $this->evaluateCondition($condition[$i]);
            }

            return $evaluatedCondition;
        }

        return false;
    }
}
