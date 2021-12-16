<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;

/**
 * Base type for compilable clause
 */
interface CompilableClauseInterface extends ClauseInterface
{
    /**
     * Get the preprocessor of the query
     *
     * @return PreprocessorInterface
     */
    public function preprocessor(): PreprocessorInterface;

    /**
     * Get the query compiler state
     *
     * @return CompilerState
     *
     * @internal
     */
    public function state(): CompilerState;

    /**
     * Tell the compiler to quote identifiers (i.e. table, columns)
     *
     * @param bool $flag
     * @return void
     */
    public function useQuoteIdentifier(bool $flag = true): void;

    /**
     * Check if the identifiers should be quoted
     *
     * @return bool
     */
    public function isQuoteIdentifier(): bool;
}
