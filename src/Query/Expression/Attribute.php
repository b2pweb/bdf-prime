<?php

namespace Bdf\Prime\Query\Expression;

/**
 * Attribute
 * 
 * The expression is a mapper attribute
 * 
 * @package Bdf\Prime\Query\Expression
 */
class Attribute implements ExpressionInterface
{
    /**
     * @var string
     */
    protected $attribute;
    
    /**
     * @var string
     */
    protected $pattern;

    /**
     * Set attribute as value
     *
     * @param string $attribute
     * @param string $pattern
     */
    public function __construct($attribute, $pattern = '%s')
    {
        $this->attribute = $attribute;
        $this->pattern = $pattern;
    }
    
    /**
     * {@inheritdoc}
     */
    public function build($query, $compiler)
    {
        return sprintf($this->pattern, $compiler->quoteIdentifier($query, $query->preprocessor()->field($this->attribute)));
    }
}