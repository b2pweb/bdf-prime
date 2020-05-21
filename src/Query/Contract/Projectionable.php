<?php

namespace Bdf\Prime\Query\Contract;

/**
 * Query which can restrict the returning data (or columns) by performing a projection
 */
interface Projectionable
{
    /**
     * Perform a projection
     * This method is an alias of select()
     *
     * @param mixed $columns The selection expressions
     *
     * @return $this
     *
     * @see Projectionable::select()
     */
    public function project($columns = null);

    /**
     * Specifies an item that is to be returned in the query result.
     * Replaces any previously specified selections, if any.
     *
     * <code>
     *     $query
     *         ->select('u.id', 'p.id')
     *         ...
     *         ;
     *
     *     $query
     *         ->select(['id', 'name' => 'n'])
     *         ...
     *         ;
     * </code>
     *
     * @param mixed $columns The selection expressions.
     *
     * @return $this This Query instance.
     */
    public function select($columns = null);

    /**
     * Adds an item that is to be returned in the query result.
     *
     * <code>
     *     $query
     *         ->select('u.id')
     *         ->addSelect('p.id')
     *         ->from('users', 'u');
     * </code>
     *
     * @param mixed $columns The selection expression.
     *
     * @return $this This Query instance.
     */
    public function addSelect($columns);
}
