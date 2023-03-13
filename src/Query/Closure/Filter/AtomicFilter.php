<?php

namespace Bdf\Prime\Query\Closure\Filter;

use Bdf\Prime\Query\Closure\Value\ComparisonValueInterface;

final class AtomicFilter
{
    public string $property;
    public string $operator;
    public ComparisonValueInterface $value;

    /**
     * @param string $property
     * @param string $operator
     * @param ComparisonValueInterface $value
     */
    public function __construct(string $property, string $operator, ComparisonValueInterface $value)
    {
        $this->property = $property;
        $this->operator = $operator;
        $this->value = $value;
    }
}
