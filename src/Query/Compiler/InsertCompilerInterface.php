<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\CompilableClause;

/**
 * Compile an insert query object to usable connection query
 *
 * @template Q as CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 */
interface InsertCompilerInterface
{
    /**
     * Compile an INSERT/REPLACE query
     *
     * @param Q $query
     *
     * @return mixed
     * @throws PrimeException
     */
    public function compileInsert(CompilableClause $query);
}
