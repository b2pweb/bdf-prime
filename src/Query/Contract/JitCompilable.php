<?php

namespace Bdf\Prime\Query\Contract;

use Bdf\Prime\Query\Compiled\CompiledSqlQuery;

/**
 * Base type for queries which can be compiled with JIT
 * Those queries can provide the compiled SQL string, and the metadata required for the execution
 */
interface JitCompilable extends SqlCompilable
{
    /**
     * Verify if the query can be actually compiled with JIT
     * Some query operation cannot be compiled with JIT, so this method should return false in such case
     *
     * @return bool
     */
    public function supportsJitCompilation(): bool;

    /**
     * Get extra query metadata, which will be passed to the compiled query
     *
     * Those metadata are values which are not directly part of the query, but are required for the execution of the query,
     * for example, the result wrapper, cache key, etc...
     *
     * @return array
     *
     * @see CompiledSqlQuery::withMetadata()
     */
    public function getMetadata(): array;

    /**
     * Get the extension set on the query
     * The extension may contain some states, which should be preserved on the compiled query
     *
     * @return object|null
     */
    public function getExtension(): ?object;
}
