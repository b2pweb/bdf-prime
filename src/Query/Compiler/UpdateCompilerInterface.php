<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\CompilableClause;

/**
 * Compile an update query object to usable connection query
 *
 * @template Q as CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 */
interface UpdateCompilerInterface
{
    /**
     * Compile update query
     *
     * @param Q $query
     *
     * @return mixed
     * @throws PrimeException
     */
    public function compileUpdate(CompilableClause $query);
}
