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
 */
class Now implements ExpressionInterface
{
    /**
     * {@inheritdoc}
     */
    public function build(CompilableClause $query, CompilerInterface $compiler)
    {
        return $compiler->platform()->grammar()->getCurrentDateSQL();
    }
}
