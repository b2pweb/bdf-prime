<?php

namespace Bdf\Prime\Schema\Manager;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Schema\TableInterface;

/**
 * Handle database operations, like load schema, check table existence,
 * manage database...
 *
 * The operations will be done one the remote connection (or simulate), so it will result to database queries
 */
interface DatabaseManagerInterface
{
    /**
     * Get the database connection instance.
     *
     * @return ConnectionInterface
     */
    public function getConnection();

    /**
     * Set the database connection instance.
     *
     * @param  ConnectionInterface $connection
     *
     * @return $this
     *
     * @internal
     */
    public function setConnection(ConnectionInterface $connection);

    /**
     * Determine if the given database exists.
     *
     * @param  string $database
     *
     * @return bool
     */
    public function hasDatabase($database);

    /**
     * Get the databases listing.
     *
     * @return array
     */
    public function getDatabases();

    /**
     * Creates a new database.
     *
     * @param string $database The name of the database to create.
     *
     * @return $this
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
     */
    public function dropDatabase($database);

    /**
     * Determine if the given table exists.
     *
     * @param  string $tableName
     *
     * @return bool
     */
    public function hasTable($tableName);

    /**
     * Determine load the table structure
     *
     * @param string $tableName
     *
     * @return TableInterface
     */
    public function loadTable($tableName);

    /**
     * Drop a table from the schema.
     *
     * @param string $tableName
     *
     * @return $this
     */
    public function drop($tableName);

    /**
     * Truncate a table
     *
     * @param string $tableName
     * @param bool $cascade
     *
     * @return $this
     */
    public function truncate($tableName, $cascade = false);

    /**
     * Rename a table on the schema.
     *
     * @param  string $from
     * @param  string $to
     *
     * @return $this
     */
    public function rename($from, $to);
}
