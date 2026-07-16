<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;

/**
 * Attribute
 *
 * The expression is a mapper attribute
 *
 * @package Bdf\Prime\Query\Expression
 *
 * @implements ExpressionInterface<CompilableClause&\Bdf\Prime\Query\Contract\Compilable, QuoteCompilerInterface>
 */
final class Field implements ExpressionInterface
{
    private string $search;
    private array $values;

    /**
     * Constructor
     *
     * @param string $search
     * @param array  $values
     */
    public function __construct(string $search, array $values)
    {
        $this->search = $search;
        $this->values = $values;
    }

    /**
     * {@inheritdoc}
     *
     * @param QuoteCompilerInterface $compiler
     *
     * @todo gestion de la platform
     */
    public function build(CompilableClause $query, object $compiler): string
    {
        // @todo only mysql ?
        return 'FIELD('.$compiler->quoteIdentifier($query, $query->preprocessor()->field($this->search)).','.implode(',', $this->values).')';
    }
}
