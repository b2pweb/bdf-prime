<?php

namespace Bdf\Prime\Query\Compiler\Preprocessor;

use Bdf\Prime\Query\CompilableClause;

/**
 * Default preprocessor for Compiler.
 * Do nothing
 */
final class DefaultPreprocessor implements PreprocessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function forInsert(CompilableClause $clause): CompilableClause
    {
        return $clause;
    }

    /**
     * {@inheritdoc}
     */
    public function forUpdate(CompilableClause $clause): CompilableClause
    {
        return $clause;
    }

    /**
     * {@inheritdoc}
     */
    public function forDelete(CompilableClause $clause): CompilableClause
    {
        return $clause;
    }

    /**
     * {@inheritdoc}
     */
    public function forSelect(CompilableClause $clause): CompilableClause
    {
        return $clause;
    }

    /**
     * {@inheritdoc}
     */
    public function field(string $attribute, mixed &$type = null): string
    {
        if ($type === true) {
            $type = null;
        }

        return $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function expression(array $expression): array
    {
        return $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function table(array $table): array
    {
        return $table;
    }

    /**
     * {@inheritdoc}
     */
    public function root(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
    }
}
