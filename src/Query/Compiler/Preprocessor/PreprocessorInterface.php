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
     * @param CompilableClause $clause
     *
     * @return CompilableClause
     */
    public function forInsert(CompilableClause $clause);

    /**
     * Prepare update Query
     *
     * @param CompilableClause $clause
     *
     * @return CompilableClause
     */
    public function forUpdate(CompilableClause $clause);

    /**
     * Prepare delete Query
     *
     * @param CompilableClause $clause
     *
     * @return CompilableClause
     */
    public function forDelete(CompilableClause $clause);

    /**
     * Prepare select Query
     *
     * @param CompilableClause $clause
     *
     * @return CompilableClause
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
    public function field($attribute, &$type = null);

    /**
     * Prepare the clause expression array
     *
     * @param array $expression
     *
     * @return array
     */
    public function expression(array $expression);

    /**
     * Prepare an FROM clause
     *
     * @param array $table
     *
     * @return array
     */
    public function table(array $table);

    /**
     * Get the root table name
     *
     * @return string
     */
    public function root();

    /**
     * Clear the preprocessor state after each compilations
     *
     * @return void
     */
    public function clear();
}
