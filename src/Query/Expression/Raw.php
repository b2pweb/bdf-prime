<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Query\CompilableClause;

/**
 * SQL Expression
 *
 * inject sql expression into query builder
 *
 * @template Q as \Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @template C as object
 * @implements ExpressionInterface<Q, C>
 */
final readonly class Raw implements ExpressionInterface
{
    public function __construct(
        private string $value,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function build(CompilableClause $query, object $compiler): string
    {
        return $this->value;
    }
}
