<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Compiler\CompilerInterface;

/**
 * Base type for perform an SGBD command
 *
 * @template C as ConnectionInterface
 */
interface CommandInterface extends CompilableClauseInterface
{
    /**
     * Gets the query language compiler
     *
     * @return CompilerInterface
     */
    public function compiler(): CompilerInterface;

    /**
     * Set the query language compiler
     *
     * @param CompilerInterface $compiler
     *
     * @return $this
     */
    public function setCompiler(CompilerInterface $compiler);

    /**
     * Gets the associated DBAL Connection for this command
     *
     * @return C
     */
    public function connection(): ConnectionInterface;

    /**
     * Set connection
     *
     * @param C $connection
     *
     * @return $this
     */
    public function on(ConnectionInterface $connection);

    /**
     * Get all matched data. Return the raw data from connection
     *
     * @param string|array $columns
     *
     * @return mixed
     * @throws PrimeException When execute fail
     */
    public function execute($columns = null);

    /**
     * Creates and adds a query root corresponding to the table identified by the
     * given alias, forming a cartesian product with any existing query roots.
     *
     * <code>
     *     $query
     *         ->select('u.id')
     *         ->from('users', 'u')
     *         ->from('customers', 'c');
     * </code>
     *
     * @param string $from The table.
     * @param string|null $alias The alias of the table.
     *
     * @return $this This Query instance.
     */
    public function from($from, $alias = null);
}
