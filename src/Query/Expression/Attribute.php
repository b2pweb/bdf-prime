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
final class Attribute implements ExpressionInterface
{
    private string $attribute;
    private string $pattern;

    /**
     * Set attribute as value
     *
     * @param string $attribute
     * @param string $pattern
     */
    public function __construct(string $attribute, string $pattern = '%s')
    {
        $this->attribute = $attribute;
        $this->pattern = $pattern;
    }

    /**
     * {@inheritdoc}
     *
     * @param QuoteCompilerInterface $compiler
     */
    public function build(CompilableClause $query, object $compiler): string
    {
        return sprintf($this->pattern, $compiler->quoteIdentifier($query, $query->preprocessor()->field($this->attribute)));
    }
}
