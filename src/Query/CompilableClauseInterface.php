<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;

/**
 * Base type for compilable clause
 *
 * @method bool|null isAllowUnknownAttribute()
 * @method void allowUnknownAttribute(bool|null $allowUnknownAttributes = true)
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

    /**
     * Does the usage of unknown attribute is allowed ?
     * If true, the compiler will not throw an exception if an attribute is not found in the entity metadata,
     * and will be used as is.
     *
     * @return bool|null
     */
    //public function isAllowUnknownAttribute(): ?bool;

    /**
     * Allow or deny the usage of unknown attribute
     *
     * If allowed, the compiler will not throw an exception if an attribute is not found in the entity metadata,
     * and will be used as is.
     *
     * @param bool|null $allowUnknownAttributes
     * @return void
     */
    //public function allowUnknownAttribute(?bool $allowUnknownAttributes = true): void;
}
