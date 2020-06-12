<?php

namespace Bdf\Prime;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\DBALException;

/**
 * ConnectionRegistryInterface
 */
interface ConnectionRegistryInterface
{
    /**
     * Get connection by name
     *
     * @param string $name Unique name of the connection to be returned
     *
     * @return ConnectionInterface
     *
     * @throws DBALException
     */
    public function getConnection(string $name): ConnectionInterface;

    /**
     * Get the loaded connection name
     *
     * @return string[] Array of connection name
     */
    public function getConnectionNames(): array;
}
