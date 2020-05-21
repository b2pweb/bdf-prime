<?php

namespace Bdf\Prime\Schema\Manager;

/**
 * Handle schema manager queries, like buffering, simulation, transaction...
 */
interface QueryManagerInterface
{
    /**
     * Push queries to execute
     *
     * @param mixed $queries Can be one or multiple queries. The type of the query depends of the platform.
     *
     * @return $this
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
     * @param \Closure|null $operations Operations to perform, or null for create a buffered SchemaManager
     *
     * @return static The simulated new SchemaManager
     */
    public function simulate(\Closure $operations = null);

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
     * @param \Closure $operations
     *
     * @return $this
     */
    public function transaction(\Closure $operations);

    /**
     * Check if the SchemaManager use a buffer
     *
     * The schema manager is marqued as buffered on :
     * @see SchemaManagerInterface::simulate()
     * @see SchemaManagerInterface::transaction()
     *
     * @return boolean
     */
    public function isBuffered();

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
     */
    public function flush();

    /**
     * Get pending queries
     *
     * @return array
     */
    public function pending();
}
