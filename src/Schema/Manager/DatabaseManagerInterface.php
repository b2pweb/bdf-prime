<?php

namespace Bdf\Prime\Schema\Manager;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Schema\TableInterface;

/**
 * Handle database operations, like load schema, check table existence,
 * manage database...
 *
 * The operations will be done one the remote connection (or simulate), so it will result to database queries
 *
 * @template C as ConnectionInterface
 */
interface DatabaseManagerInterface
{
    /**
     * Get the database connection instance.
     *
     * @return C
     */
    public function getConnection();

    /**
     * Set the database connection instance.
     *
     * @param  C $connection
     *
     * @return $this
     *
     * @internal
     * @throws PrimeException
     */
    public function setConnection(ConnectionInterface $connection);

    /**
     * Determine if the given database exists.
     *
     * @param  string $database
     *
     * @return bool
     * @throws PrimeException When list databases fail
     */
    public function hasDatabase($database);

    /**
     * Get the databases listing.
     *
     * @return array
     * @throws PrimeException When list databases fail
     */
    public function getDatabases();

    /**
     * Creates a new database.
     *
     * @param string $database The name of the database to create.
     *
     * @return $this
     * @throws PrimeException When query fail
     */
    public function createDatabase($database);

    /**
     * Drops a database.
     *
     * NOTE: You can not drop the database this connection is currently connected to.
     *
     * @param string $database The name of the database to drop.
     *
     * @return $this
     * @throws PrimeException When query fail
     */
    public function dropDatabase($database);

    /**
     * Determine if the given table exists.
     *
     * @param  string $tableName
     *
     * @return bool
     * @throws PrimeException When query fail
     */
    public function hasTable($tableName);

    /**
     * Determine load the table structure
     *
     * @param string $tableName
     *
     * @return TableInterface
     * @throws PrimeException When query fail
     */
    public function loadTable($tableName);

    /**
     * Drop a table from the schema.
     *
     * @param string $tableName
     *
     * @return $this
     * @throws PrimeException When query fail
     */
    public function drop($tableName);

    /**
     * Truncate a table
     *
     * @param string $tableName
     * @param bool $cascade
     *
     * @return $this
     * @throws PrimeException When query fail
     */
    public function truncate($tableName, $cascade = false);

    /**
     * Rename a table on the schema.
     *
     * @param  string $from
     * @param  string $to
     *
     * @return $this
     * @throws PrimeException When query fail
     */
    public function rename($from, $to);
}
