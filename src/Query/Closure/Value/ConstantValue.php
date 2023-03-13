<?php

namespace Bdf\Prime\Query\Closure\Value;

use Bdf\Prime\Query\Closure\Value\ComparisonValueInterface;
use Closure;
use ReflectionFunction;

/**
 * Handle simple constant value expression
 */
final class ConstantValue implements ComparisonValueInterface
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @param mixed $value The constant value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Get the constant value
     *
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function get(ReflectionFunction $reflection)
    {
        return $this->value;
    }
}
