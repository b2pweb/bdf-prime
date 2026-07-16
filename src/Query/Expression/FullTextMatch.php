<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\SqlCompiler;

use function sprintf;

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
final class FullTextMatch implements ExpressionInterface
{
    private string $search;
    private string $value;
    private bool $booleanMode;

    /**
     * Constructor
     *
     * @param string  $search
     * @param string $value
     * @param boolean $booleanMode
     */
    public function __construct(string $search, string $value, bool $booleanMode = false)
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
    public function build(CompilableClause $query, object $compiler): string
    {
        return sprintf(
            'MATCH(%s AGAINST(%s)%s)',
            $compiler->quoteIdentifier($query, $query->preprocessor()->field($this->search)),
            $compiler->quote($this->value),
            $this->booleanMode ? ' IN BOOLEAN MODE' : '',
        );
    }
}
