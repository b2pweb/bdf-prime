<?php

namespace Bdf\Prime\Connection;

/**
 * Base type for handle transactions on a connection
 *
 * @method bool isTransactionActive()
 * @method bool isNestedTransactionEnabled()
 * @method bool useNestedTransaction(bool $flag = true)
 * @method mixed inTransaction(callable $task)
 * @method mixed inNestedTransaction(callable $task)
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

    /**
     * Check if a transaction has been started.
     *
     * @return bool TRUE if a transaction is currently active, FALSE otherwise.
     * @see TransactionManagerInterface::beginTransaction() To start a transaction
     */
    //public function isTransactionActive(): bool;

    /**
     * Check if nested transaction (using save point) is enabled.
     */
    //public function isNestedTransactionEnabled(): bool;

    /**
     * Enable or disable nested transaction (using save point).
     *
     * @return bool The previous state of the nested transaction
     */
    //public function useNestedTransaction(bool $flag = true): bool;

    /**
     * Execute the given task in a transaction.
     *
     * If a transaction is not active, it will start a new transaction.
     * If a transaction is already active, it will start a new transaction if nested transactions are enabled, or directly use the current one.
     *
     * If this method does not start a new transaction, it will not commit or rollback the transaction.
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
    //public function inTransaction(callable $task);

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
