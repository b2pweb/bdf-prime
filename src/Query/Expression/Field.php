<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;

/**
 * Attribute
 * 
 * The expression is a mapper attribute
 * 
 * @package Bdf\Prime\Query\Expression
 */
class Field implements ExpressionInterface
{
    /**
     * @var string
     */
    protected $search;
    
    /**
     * @var array
     */
    protected $values;
    
    /**
     * Constructor
     * 
     * @param string $search
     * @param array  $values
     */
    public function __construct($search, array $values)
    {
        $this->search = $search;
        $this->values = $values;
    }
    
    /**
     * {@inheritdoc}
     * 
     * @todo gestion de la platform
     */
    public function build(CompilableClause $query, CompilerInterface $compiler)
    {
//        if ($compiler->platform()->name() === 'mysql') {
            return 'FIELD('.$compiler->quoteIdentifier($query, $query->preprocessor()->field($this->search)).','.implode(',', $this->values).')';
//        }
//        
//        return '';
    }
}