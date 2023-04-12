<?php

namespace Bdf\Prime\Query\Closure\Value;

use ReflectionFunction;

/**
 * Get value from bound variable
 */
final class VariableValue implements ComparisonValueInterface
{
    private string $variable;

    /**
     * @param string $variable The variable name
     */
    public function __construct(string $variable)
    {
        $this->variable = $variable;
    }

    /**
     * {@inheritdoc}
     */
    public function get(ReflectionFunction $reflection)
    {
        if ($this->variable === 'this') {
            return $reflection->getClosureThis();
        }

        return $reflection->getStaticVariables()[$this->variable];
    }
}
