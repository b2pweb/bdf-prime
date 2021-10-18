<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\CompilableClause;

/**
 * Compile a query object to usable connection query
 *
 * @template Q as CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 */
interface CompilerInterface
{
    /**
     * Converts query into a INSERT/REPLACE string in SQL.
     *
     * @param Q $query
     *
     * @return string
     * @throws PrimeException
     */
    public function compileInsert(CompilableClause $query);

    /**
     * Converts query into a UPDATE string in SQL.
     *
     * @param Q $query
     *
     * @return string
     * @throws PrimeException
     */
    public function compileUpdate(CompilableClause $query);

    /**
     * Converts query into a DELETE string in SQL.
     *
     * @param Q $query
     *
     * @return string
     * @throws PrimeException
     */
    public function compileDelete(CompilableClause $query);

    /**
     * Converts query into a SELECT string in SQL.
     *
     * @param Q $query
     *
     * @return string
     * @throws PrimeException
     */
    public function compileSelect(CompilableClause $query);

    /**
     * Gets the connection platform
     *
     * @return PlatformInterface
     * @throws PrimeException
     */
    public function platform();

    /**
     * Quote a identifier
     *
     * @param Q $query
     * @param string $column
     *
     * @return string
     * @throws PrimeException
     */
    public function quoteIdentifier(CompilableClause $query, $column);

    /**
     * @todo Supprimer ? Il est plus logique que ce soit la query elle même qui gère ses bindings. En l'état impossible, mais à voir pour gérer une autre stratégie de gestion des bindings
     *
     * @param Q $query
     *
     * @return array
     *
     * @throws PrimeException
     */
    public function getBindings(CompilableClause $query);
}
