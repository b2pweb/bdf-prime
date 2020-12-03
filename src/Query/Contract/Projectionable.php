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
     * To define an alias, an associative array must be used, with the alias as key, and expression as value.
     *
     * Note: To ensure that expressions string will not be parsed, use expression objects, or wrap with `new Raw('...')`
     *
     * <code>
     *     // SELECT u.id, p.id
     *     $query
     *         ->select('u.id', 'p.id')
     *         ...
     *         ;
     *
     *     // With alias : SELECT id, n as name
     *     $query
     *         ->select(['id', 'name' => 'n'])
     *         ...
     *         ;
     *
     *     // Use expression : SELECT max(id) as maxId
     *     $query
     *         ->select(['maxId' => new Raw('max(id)')])
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
     * To define an alias, an associative array must be used, with the alias as key, and expression as value.
     *
     * Note: To ensure that expressions string will not be parsed, use expression objects, or wrap with `new Raw('...')`
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
     * @see Projectionable::select() for exemples
     */
    public function addSelect($columns);
}
