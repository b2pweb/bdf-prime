<?php

namespace Bdf\Prime\Query\Expression;

/**
 * Match
 * 
 * The fulltext search expression
 * 
 * @package Bdf\Prime\Query\Expression
 */
class Match implements ExpressionInterface
{
    /**
     * @var string
     */
    protected $search;
    
    /**
     * @var mixed
     */
    protected $value;
    
    /**
     * @var bool
     */
    protected $booleanMode;
    
    /**
     * Constructor
     * 
     * @param string  $search
     * @param array   $value
     * @param boolean $booleanMode
     */
    public function __construct($search, $value, $booleanMode = false)
    {
        $this->search = $search;
        $this->value = $value;
        $this->booleanMode = $booleanMode;
    }
    
    /**
     * FULLTEXT search
     * 
     * {@inheritdoc}
     */
    public function build($query, $compiler)
    {
        $sql = 'MATCH('.$compiler->quoteIdentifier($query, $query->preprocessor()->field($this->search)).' AGAINST('.$compiler->quote($this->value).')';
        
        if ($this->booleanMode) {
            $sql .= ' IN BOOLEAN MODE)';
        } else {
            $sql .= ')';
        }
        
        return $sql;
    }
}