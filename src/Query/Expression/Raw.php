<?php

namespace Bdf\Prime\Query\Expression;

/**
 * SQL Expression
 * 
 * inject sql expression into query builder
 * 
 * @package Bdf\Prime\Query\Expression
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
    public function build($query, $compiler)
    {
        return $this->__toString();
    }
}