<?php

namespace Bdf\Prime\Query\Criteria;

use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Structure for a single filter for the query
 *
 * @psalm-immutable
 */
final class FilterEntry
{
    /**
     * @var string|ExpressionInterface
     */
    /*readonly string|ExpressionInterface*/ public $field;
    /*readonly*/ public string $operator;
    /*readonly mixed*/ public $value;

    /**
     * @param ExpressionInterface|string $field
     * @param string $operator
     * @param mixed $value
     */
    public function __construct($field, string $operator, $value)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }
}
