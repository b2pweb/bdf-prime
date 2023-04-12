<?php

namespace Bdf\Prime\Query\Closure\Value;

use ReflectionFunction;

/**
 * Handle property access expression
 */
final class PropertyValue implements ComparisonValueInterface
{
    private ComparisonValueInterface $value;
    private string $property;

    /**
     * @param ComparisonValueInterface $value Object value expression
     * @param string $property The property name
     */
    public function __construct(ComparisonValueInterface $value, string $property)
    {
        $this->value = $value;
        $this->property = $property;
    }

    /**
     * {@inheritdoc}
     */
    public function get(ReflectionFunction $reflection)
    {
        return $this->value->get($reflection)->{$this->property};
    }
}
