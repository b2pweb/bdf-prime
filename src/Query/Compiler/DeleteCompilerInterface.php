<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Contract\Compilable;

/**
 * Compile a delete query object to usable connection query
 *
 * @template Q as CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 */
interface DeleteCompilerInterface
{
    /**
     * Compile a delete query
     *
     * @param Q $query
     *
     * @return mixed
     * @throws PrimeException
     */
    public function compileDelete(CompilableClause $query);
}
