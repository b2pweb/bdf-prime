<?php

namespace Bdf\Prime\Schema\Manager;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Schema\TableInterface;

/**
 * Handle table management in interactive way
 *
 * - The operations are done only on tables
 * - All operations will results to a diff, an changes will be applied (or simulated)
 */
interface TableManagerInterface
{
    /**
     * Set the use drop flag
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function useDrop($flag = true);

    /**
     * Get the use drop flag
     *
     * @return bool
     */
    public function getUseDrop();

    /**
     * Create table or extract alteration on existing table.
     *
     * <code>
     * $schemaManager->table('person', function (TypesHelperTableBuilder $table) {
     *     $table
     *         ->string('first_name')
     *         ->string('last_name')
     *         ->integer('age')
     *     ;
     * });
     * </code>
     *
     * @param  string $tableName The name of the table
     * @param  callable $callback The table build method
     *
     * @return $this
     *
     * @see \Bdf\Prime\Schema\Builder\TypesHelperTableBuilder
     * @throws PrimeException
     */
    public function table($tableName, callable $callback);

    /**
     * Add a table to the schema.
     * - If the table do not exists, it'll be created
     * - If the table exists, but differs, change the table
     * - Else, do nothing
     *
     * @param TableInterface $table
     *
     * @return $this
     * @throws PrimeException
     */
    public function add(TableInterface $table);

    /**
     * Change table.
     *
     * @param  string $tableName
     * @param  callable $callback
     *
     * @return $this
     * @throws PrimeException
     */
    public function change($tableName, callable $callback);
}
