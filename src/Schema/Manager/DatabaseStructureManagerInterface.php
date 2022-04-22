<?php

namespace Bdf\Prime\Schema\Manager;

use Bdf\Prime\Exception\PrimeException;

/**
 * Handle loading and altering database structure
 * Permit to load, add and compute diff of database structures (i.e. table on SQL)
 *
 * @template T as object
 */
interface DatabaseStructureManagerInterface
{
    /**
     * Load the table structure
     *
     * @param string $name
     *
     * @return T
     * @throws PrimeException When query fail
     */
    public function load(string $name);

    /**
     * Add a table to the schema.
     * - If the table do not exists, it'll be created
     * - If the table exists, but differs, change the table
     * - Else, do nothing
     *
     * @param T $structure
     *
     * @return $this
     * @throws PrimeException
     */
    public function add($structure);

    /**
     * Get the diff queries from two tables
     *
     * @param T $new
     * @param T $old
     *
     * @return mixed
     *
     * @internal The return value depends on the platform. You should not rely on the return of this method
     */
    public function diff($new, $old);
}
