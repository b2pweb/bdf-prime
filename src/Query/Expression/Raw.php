<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;

use function trigger_error;

/**
 * SQL Expression
 *
 * inject sql expression into query builder
 *
 * @package Bdf\Prime\Query\Expression
 * @final
 */
class Raw implements ExpressionInterface
{
    protected string $value;

    /**
     * Instanciate a new raw sql
     *
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = (string) $value;
    }

    /**
     * Get raw string
     *
     * @return string
     */
    public function __toString()
    {
        @trigger_error('Using Raw expression as string is deprecated since Prime 2.2, and will be removed in prime 3.0.', E_USER_DEPRECATED);

        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function build(CompilableClause $query, object $compiler): string
    {
        return $this->value;
    }
}
