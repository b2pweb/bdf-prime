<?php

namespace Bdf\Prime\Bus\Command;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\TransactionManagerInterface;

/**
 * Handle transaction on multiple connections
 */
final class TransactionManager
{
    /**
     * @var array<string, ConnectionInterface&TransactionManagerInterface>
     */
    private array $connections = [];

    /**
     * Try to start a transaction on the given connection
     * If the transaction is already started, will do nothing
     *
     * @param ConnectionInterface $connection The connection to start the transaction
     * @return bool true if the transaction is started, false if the connection does not support transaction
     */
    public function start(ConnectionInterface $connection): bool
    {
        $name = $connection->getName();

        if (isset($this->connections[$name])) {
            return true;
        }

        if (!$connection instanceof TransactionManagerInterface) {
            return false;
        }

        $this->connections[$name] = $connection;
        return $connection->beginTransaction();
    }

    /**
     * Commit all changes on all connections
     */
    public function commit(): void
    {
        foreach ($this->connections as $connection) {
            $connection->commit();
        }

        $this->connections = [];
    }

    /**
     * Rollback all changes on all connections
     */
    public function rollback(): void
    {
        foreach ($this->connections as $connection) {
            $connection->rollback();
        }

        $this->connections = [];
    }

    public function __destruct()
    {
        $this->rollback();
    }
}
