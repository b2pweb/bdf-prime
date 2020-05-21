<?php

namespace Bdf\Prime\Query\Expression;

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
    public function build($query, $compiler)
    {
        return $compiler->platform()->grammar()->getCurrentDateSQL();
    }
}