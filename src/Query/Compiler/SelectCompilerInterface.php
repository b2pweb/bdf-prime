<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Contract\Compilable;

/**
 * Compile a select query object to usable connection query
 *
 * @template Q as CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 */
interface SelectCompilerInterface
{
    /**
     * Compile a search/select query
     *
     * @param Q $query
     *
     * @return mixed
     * @throws PrimeException
     *
     * @see Compilable::TYPE_SELECT
     */
    public function compileSelect(CompilableClause $query);
}
