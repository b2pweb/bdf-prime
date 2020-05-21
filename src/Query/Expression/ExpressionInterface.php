<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;

/**
 * SQL Expression
 * 
 * inject sql expression into query builder
 * 
 * @package Bdf\Prime\Query\Expression
 */
interface ExpressionInterface
{
    /**
     * Build the expression on query builder
     *
     * @param CompilableClause $query
     * @param CompilerInterface $compiler
     *
     * @return string
     */
    public function build($query, $compiler);
}