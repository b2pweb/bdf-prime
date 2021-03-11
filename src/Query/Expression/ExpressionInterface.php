<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Exception\PrimeException;
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
     * @param Q $query
     * @param CompilerInterface<Q> $compiler
     *
     * @return string
     * @throws PrimeException
     *
     * @template Q as \Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable
     */
    public function build(CompilableClause $query, CompilerInterface $compiler);
}
