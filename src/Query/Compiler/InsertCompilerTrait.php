<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\CompilableClause;

/**
 * @template Q as CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @psalm-require-implements InsertCompilerInterface
 */
trait InsertCompilerTrait
{
    /**
     * {@inheritdoc}
     *
     * @param Q $query
     */
    public function compileInsert(CompilableClause $query)
    {
        try {
            $query->state()->compiling = true;
            $query = $query->preprocessor()->forInsert($query);

            return $this->doCompileInsert($query);
        } finally {
            $query->state()->compiling = false;
            $query->preprocessor()->clear();
        }
    }

    /**
     * Compile an insert query
     *
     * @param Q $query
     *
     * @return mixed
     * @throws PrimeException
     */
    abstract protected function doCompileInsert(CompilableClause $query);
}
