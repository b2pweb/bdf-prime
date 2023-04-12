<?php

namespace Bdf\Prime\Query\Closure\Value;

use Bdf\Prime\Query\Closure\Value\ComparisonValueInterface;
use ReflectionFunction;

/**
 * Handle array expression value
 */
class ArrayValue implements ComparisonValueInterface
{
    /**
     * @var array<array-key, ComparisonValueInterface>
     */
    private array $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * {@inheritdoc}
     */
    public function get(ReflectionFunction $reflection)
    {
        $values = [];

        foreach ($this->values as $key => $value) {
            $values[$key] = $value->get($reflection);
        }

        return $values;
    }
}
