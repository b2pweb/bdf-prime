<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Platform\PlatformInterface;

/**
 * Compile a query object to usable connection query
 *
 * @template Q as \Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 *
 * @extends SelectCompilerInterface<Q>
 * @extends UpdateCompilerInterface<Q>
 * @extends InsertCompilerInterface<Q>
 * @extends DeleteCompilerInterface<Q>
 * @extends BindingsCompilerInterface<Q>
 */
interface CompilerInterface extends SelectCompilerInterface, UpdateCompilerInterface, InsertCompilerInterface, DeleteCompilerInterface, BindingsCompilerInterface
{
    /**
     * Gets the connection platform
     *
     * @return PlatformInterface
     * @throws PrimeException
     */
    public function platform(): PlatformInterface;
}
