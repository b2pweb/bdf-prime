<?php

namespace Bdf\Prime\Schema;

/**
 * Set of table indexes
 *
 * Implementations should be immutable
 *
 * An index set represents a set of index, with a primary key
 * The indexes names are case insensitive
 */
interface IndexSetInterface
{
    /**
     * Get the primary index
     *
     * @return IndexInterface|null
     */
    public function primary();

    /**
     * Get list of all indexes
     *
     * @return array<string, IndexInterface>
     */
    public function all();

    /**
     * Get one index by its name
     *
     * @param string $name
     *
     * @return IndexInterface
     */
    public function get($name);

    /**
     * Check if an index exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);
}
