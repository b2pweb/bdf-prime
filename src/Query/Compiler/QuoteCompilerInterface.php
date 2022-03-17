<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\CompilableClause;

/**
 * Compiler type for quote identifiers or values
 *
 * @template Q as CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 */
interface QuoteCompilerInterface
{
    /**
     * Quote a identifier
     *
     * @param Q $query
     * @param string $column
     *
     * @return string
     * @throws PrimeException
     */
    public function quoteIdentifier(CompilableClause $query, string $column): string;

    /**
     * Quote a value
     *
     * @param mixed $value
     *
     * @return scalar
     * @throws PrimeException
     */
    public function quote($value);
}
