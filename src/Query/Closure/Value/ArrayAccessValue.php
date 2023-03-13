<?php

namespace Bdf\Prime\Query\Closure\Value;

use ReflectionFunction;

/**
 * Handle array access
 */
final class ArrayAccessValue implements ComparisonValueInterface
{
    private ComparisonValueInterface $value;
    private ComparisonValueInterface $key;

    /**
     * @param ComparisonValueInterface $value
     * @param ComparisonValueInterface $key
     */
    public function __construct(ComparisonValueInterface $value, ComparisonValueInterface $key)
    {
        $this->value = $value;
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function get(ReflectionFunction $reflection)
    {
        $value = $this->value->get($reflection);
        $key = $this->key->get($reflection);

        return $value[$key];
    }
}
