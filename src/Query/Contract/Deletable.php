<?php

namespace Bdf\Prime\Query\Contract;

/**
 * Query which can perform delete operation
 * This query may also implements @see Whereable for filter rows to delete
 */
interface Deletable
{
    /**
     * Delete entities from a given table
     *
     * <code>
     *     $query
     *         ->from('users')
     *         ->where('id', 1)
     *         ->delete();
     * </code>
     *
     * @return int The number of deleted rows
     */
    public function delete();
}
