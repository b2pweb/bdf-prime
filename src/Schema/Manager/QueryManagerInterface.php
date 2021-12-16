<?php

namespace Bdf\Prime\Schema\Manager;

use Bdf\Prime\Exception\PrimeException;
use Closure;
use Exception;

/**
 * Handle schema manager queries, like buffering, simulation, transaction...
 */
interface QueryManagerInterface
{
    /**
     * Push queries to execute
     *
     * @param mixed $queries Can be one or multiple queries. The type of the query depends on the platform.
     *
     * @return $this
     * @throws PrimeException If auto flush is enabled and the query fail
     */
    public function push($queries);

    /**
     * Simulate operations on schema
     *
     * <code>
     * // Get changes
     * $schema->simulate(function (SchemaManagerInterface $schema) {
     *     $schema->table(...);
     *     $schema->drop(...);
     * })->pending();
     *
     * $buffered = $schema->simulate();
     * $buffered->table(...);
     * $buffered->flush();
     * </code>
     *
     * To perform operations, you should use @see SchemaManagerInterface::flush()
     *
     * @param callable(static):void|null $operations Operations to perform, or null for create a buffered SchemaManager
     *
     * @return static The simulated new SchemaManager
     */
    public function simulate(callable $operations = null);

    /**
     * Do operations into a transaction.
     * A transaction is "All or nothing"
     *
     * <code>
     * $schema->simulate(function (SchemaManagerInterface $schema) {
     *     $schema->table(...);
     *     $schema->truncate(...);
     * });
     * </code>
     *
     * @param callable(static):void $operations
     *
     * @return $this
     *
     * @throws PrimeException When transaction fail
     * @throws Exception Rethrow $operations exception
     * @throws \BadMethodCallException When the connection do not supports transations
     */
    public function transaction(callable $operations);

    /**
     * Check if the SchemaManager use a buffer
     *
     * The schema manager is marqued as buffered on :
     * @see SchemaManagerInterface::simulate()
     * @see SchemaManagerInterface::transaction()
     *
     * @return boolean
     */
    public function isBuffered(): bool;

    /**
     * Clear the cached queries
     *
     * @return $this
     */
    public function clear();

    /**
     * Execute the modification to build / modify the schema.
     * This method do nothing if it's not buffered
     *
     * @see SchemaManager::isBuffered()
     * @see SchemaManager::simulate()
     * @see SchemaManager::transaction()
     *
     * @return bool
     *
     * @throws PrimeException When a pending query fail
     */
    public function flush(): bool;

    /**
     * Get pending queries
     *
     * @return array
     */
    public function pending(): array;
}
