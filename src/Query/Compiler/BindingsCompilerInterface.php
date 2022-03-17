<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\CompilableClause;

/**
 * Compile bindings of a query
 *
 * @template Q as CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 */
interface BindingsCompilerInterface
{
    /**
     * Get bindings (i.e. prepared parameters values) of the query
     *
     * @param Q $query
     *
     * @return array
     *
     * @throws PrimeException
     */
    public function getBindings(CompilableClause $query): array;
}
