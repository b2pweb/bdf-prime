<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\SqlCompiler;

/**
 * FullTextMatch
 *
 * The fulltext search expression
 *
 * @package Bdf\Prime\Query\Expression
 *
 * @template Q as \Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\SqlQueryInterface
 * @implements ExpressionInterface<Q, \Bdf\Prime\Query\Compiler\SqlCompiler>
 */
class FullTextMatch implements ExpressionInterface
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
     * {@inheritdoc}
     *
     * @param Q $query
     * @param SqlCompiler $compiler
     */
    public function build(CompilableClause $query, object $compiler)
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
