<?php

namespace Bdf\Prime\Connection;

/**
 * Base type for handle transactions on a connection
 *
 * @method mixed inNestedTransaction(callable $task)
 */
interface TransactionManagerInterface
{
    /**
     * Initiates a transaction.
     */
    public function beginTransaction(): void;

    /**
     * Commits a transaction.
     */
    public function commit(): void;

    /**
     * Rolls back the current transaction, as initiated by beginTransaction().
     */
    public function rollBack(): void;

    /**
     * Check if a transaction has been started.
     *
     * @return bool TRUE if a transaction is currently active, FALSE otherwise.
     * @see TransactionManagerInterface::beginTransaction() To start a transaction
     */
    public function isTransactionActive(): bool;

    /**
     * Execute the given task in a new transaction.
     *
     * This is mostly equivalent to the following code:
     * ```php
     * $connection->beginTransaction();
     * try {
     *     $result = $task();
     *     $connection->commit();
     * } catch (\Throwable $e) {
     *     $connection->rollBack();
     *     throw $e;
     * }
     * ```
     *
     * @param callable():T $task
     * @return T
     * @template T as mixed
     */
    public function inTransaction(callable $task): mixed;

    /**
     * Execute the given task in a nested transaction.
     * Unlike {@see TransactionManagerInterface::inTransaction()} a new transaction will always be started.
     *
     * @param callable():T $task
     * @return T
     * @template T as mixed
     */
    //public function inNestedTransaction(callable $task);
}
