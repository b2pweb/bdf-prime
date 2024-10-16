<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;

/**
 * SQL Expression
 *
 * inject sql expression into query builder
 *
 * @template Q as \Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @template C as object
 * @implements ExpressionInterface<Q, C>
 */
class Raw implements ExpressionInterface
{
    /**
     * @var string
     */
    protected $value;

    /**
     * Instanciate a new raw sql
     *
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Get raw string
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function build(CompilableClause $query, object $compiler)
    {
        return $this->__toString();
    }
}
