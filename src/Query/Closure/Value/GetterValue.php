<?php

namespace Bdf\Prime\Query\Closure\Value;

use ReflectionFunction;

/**
 * Call a getter to get the value
 */
final class GetterValue implements ComparisonValueInterface
{
    private ComparisonValueInterface $value;
    private string $method;

    /**
     * @param ComparisonValueInterface $value Object value expression
     * @param string $method Getter method name
     */
    public function __construct(ComparisonValueInterface $value, string $method)
    {
        $this->value = $value;
        $this->method = $method;
    }

    /**
     * {@inheritdoc}
     */
    public function get(ReflectionFunction $reflection)
    {
        return $this->value->get($reflection)->{$this->method}();
    }
}
