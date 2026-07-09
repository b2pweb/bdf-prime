<?php

namespace Bdf\Prime\Query\Criteria;

use Attribute;
use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Define that the marked property should be used as a filter for the query
 * This class can be inherited to define custom filters
 *
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Criterion
{
    /**
     * The field name of the filter
     * An expression can be used instead of a simple field name
     * If null, will use the property name instead
     *
     * @var string|ExpressionInterface|null
     */
    public string|ExpressionInterface|null $field;

    /**
     * Define the operator to use for the filter
     * If null, the default operator will be used (usually '=')
     */
    public ?string $operator;

    /**
     * Does the filter should be ignored if the value is null?
     */
    public bool $skipNull;

    /**
     * @param ExpressionInterface|string|null $field
     * @param string|null $operator
     * @param bool $skipNull
     */
    public function __construct($field = null, ?string $operator = null, bool $skipNull = true)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->skipNull = $skipNull;
    }

    /**
     * Get the field to search on
     * An object can be returned to use a custom expression instead of a field
     *
     * @param string $property The criteria property name
     * @return string|ExpressionInterface The field to search on, or expression to use
     */
    public function field(string $property)
    {
        return $this->field ?? $property;
    }

    /**
     * Transform the property value before applying the filter
     * This methods can be overridden to apply custom transformation
     *
     * @param mixed $value The property value
     * @return mixed The transformed value that will be used on the query
     */
    public function value($value)
    {
        return $value;
    }
}
