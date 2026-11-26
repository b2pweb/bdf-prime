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
    private mixed $value;

    /**
     * @param mixed $value The constant value
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * Get the constant value
     *
     * @return mixed
     */
    public function value(): mixed
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function get(ReflectionFunction $reflection): mixed
    {
        return $this->value;
    }
}
