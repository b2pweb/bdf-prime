<?php

namespace Bdf\Prime\Query\Criteria;

use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Structure for a single filter for the query
 *
 * @psalm-immutable
 */
final readonly class FilterEntry
{
    public string|ExpressionInterface $field;
    public string $operator;
    public mixed $value;

    /**
     * @param ExpressionInterface|string $field
     * @param string $operator
     * @param mixed $value
     */
    public function __construct(ExpressionInterface|string $field, string $operator, mixed $value)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }
}
