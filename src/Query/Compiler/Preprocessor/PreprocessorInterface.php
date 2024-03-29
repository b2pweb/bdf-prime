<?php

namespace Bdf\Prime\Query\Compiler\Preprocessor;

use Bdf\Prime\Query\CompilableClause;

/**
 * Interface PreprocessorInterface
 */
interface PreprocessorInterface
{
    /**
     * Prepare insert Query
     *
     * @param Q $clause
     *
     * @return Q
     *
     * @template Q as CompilableClause
     */
    public function forInsert(CompilableClause $clause);

    /**
     * Prepare update Query
     *
     * @param Q $clause
     *
     * @return Q
     *
     * @template Q as CompilableClause
     */
    public function forUpdate(CompilableClause $clause);

    /**
     * Prepare delete Query
     *
     * @param Q $clause
     *
     * @return Q
     *
     * @template Q as CompilableClause
     */
    public function forDelete(CompilableClause $clause);

    /**
     * Prepare select Query
     *
     * @param Q $clause
     *
     * @return Q
     *
     * @template Q as CompilableClause
     */
    public function forSelect(CompilableClause $clause);

    /**
     * Get formatted field from attribute alias
     *
     * @param string $attribute
     * @param mixed  $type      In-out. If set to true the method will inject the type object of the attribute
     *
     * @return string
     */
    public function field(string $attribute, &$type = null): string;

    /**
     * Prepare the clause expression array
     *
     * @param array $expression
     *
     * @return array
     */
    public function expression(array $expression): array;

    /**
     * Prepare an FROM clause
     *
     * @param array{table: string, alias: string|null} $table
     *
     * @return array{table: string, alias: string|null}
     */
    public function table(array $table): array;

    /**
     * Get the root table name
     *
     * @return string|null
     */
    public function root(): ?string;

    /**
     * Clear the preprocessor state after each compilations
     *
     * @return void
     */
    public function clear(): void;
}
