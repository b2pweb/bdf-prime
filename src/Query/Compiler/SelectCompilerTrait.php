<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\CompilableClause;

/**
 * @template Q as CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @psalm-require-implements SelectCompilerInterface
 */
trait SelectCompilerTrait
{
    /**
     * {@inheritdoc}
     *
     * @param Q $query
     */
    public function compileSelect(CompilableClause $query)
    {
        try {
            $query->state()->compiling = true;
            $query = $query->preprocessor()->forSelect($query);

            return $this->doCompileSelect($query);
        } finally {
            $query->state()->compiling = false;
            $query->preprocessor()->clear();
        }
    }

    /**
     * Compile a select query
     *
     * @param Q $query
     *
     * @return mixed
     * @throws PrimeException
     */
    abstract protected function doCompileSelect(CompilableClause $query);
}
