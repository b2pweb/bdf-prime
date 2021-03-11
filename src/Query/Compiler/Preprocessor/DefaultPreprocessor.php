<?php

namespace Bdf\Prime\Query\Compiler\Preprocessor;

use Bdf\Prime\Query\CompilableClause;

/**
 * Default preprocessor for Compiler.
 * Do nothing
 */
class DefaultPreprocessor implements PreprocessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function forInsert(CompilableClause $clause)
    {
        return $clause;
    }

    /**
     * {@inheritdoc}
     */
    public function forUpdate(CompilableClause $clause)
    {
        return $clause;
    }

    /**
     * {@inheritdoc}
     */
    public function forDelete(CompilableClause $clause)
    {
        return $clause;
    }

    /**
     * {@inheritdoc}
     */
    public function forSelect(CompilableClause $clause)
    {
        return $clause;
    }

    /**
     * {@inheritdoc}
     */
    public function field(string $attribute, &$type = null): string
    {
        if ($type === true) {
            $type = null;
        }

        return $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function expression(array $expression)
    {
        return $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function table(array $table)
    {
        return $table;
    }

    /**
     * {@inheritdoc}
     */
    public function root()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {

    }
}
