<?php

namespace Bdf\Prime\Connection;

/**
 * Base type for handle transactions on a connection
 */
interface TransactionManagerInterface
{
    /**
     * Initiates a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function beginTransaction(): bool;

    /**
     * Commits a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function commit(): bool;

    /**
     * Rolls back the current transaction, as initiated by beginTransaction().
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function rollBack(): bool;
}
