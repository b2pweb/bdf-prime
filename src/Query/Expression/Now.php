<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;

/**
 * Now
 * 
 * create the 'now'NOW()" function expression
 * 
 * @package Bdf\Prime\Query\Expression
 *
 *
 * @implements ExpressionInterface<CompilableClause&\Bdf\Prime\Query\Contract\Compilable, CompilerInterface>
 */
class Now implements ExpressionInterface
{
    /**
     * {@inheritdoc}
     *
     * @param CompilerInterface $compiler
     */
    public function build(CompilableClause $query, object $compiler)
    {
        return $compiler->platform()->grammar()->getCurrentDateSQL();
    }
}
