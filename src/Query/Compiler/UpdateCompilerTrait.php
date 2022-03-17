<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\CompilableClause;

/**
 * @template Q as CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @psalm-require-implements UpdateCompilerInterface
 */
trait UpdateCompilerTrait
{
    /**
     * {@inheritdoc}
     *
     * @param Q $query
     */
    public function compileUpdate(CompilableClause $query)
    {
        try {
            $query->state()->compiling = true;
            $query = $query->preprocessor()->forUpdate($query);

            return $this->doCompileUpdate($query);
        } finally {
            $query->state()->compiling = false;
            $query->preprocessor()->clear();
        }
    }

    /**
     * Compile an update query
     *
     * @param Q $query
     *
     * @return mixed
     * @throws PrimeException
     */
    abstract protected function doCompileUpdate(CompilableClause $query);
}
