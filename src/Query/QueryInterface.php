<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Contract\Deletable;
use Bdf\Prime\Query\Contract\Projectionable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\Whereable;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\Pagination\PaginatorInterface;

/**
 * QueryInterface
 *
 * @template C as \Bdf\Prime\Connection\ConnectionInterface
 * @template R as object|array
 *
 * @extends ReadCommandInterface<C, R>
 */
interface QueryInterface extends ReadCommandInterface, Whereable, Projectionable, Compilable, Deletable
{
    /**
     * Turns the query being built into a bulk update query that ranges over
     * a certain table
     *
     * <code>
     *     $query
     *         ->from('users', 'u')
     *         ->update(['u.password' => md5('password')]);
     * </code>
     *
     * @param array $data
     * @param array $types
     *
     * @return int The number of updated rows
     * @throws PrimeException When execute fail
     */
    #[WriteOperation]
    public function update(array $data = [], array $types = []);

    /**
     * Set update values
     *
     * <code>
     *     $query
     *         ->from('users', 'u');
     *         ->where('u.id = ?');
     *         ->set('u.password', md5('password'), 'string')
     *         ->update()
     * </code>
     *
     * @param string $column
     * @param mixed $value
     * @param mixed $type
     *
     * @return $this This Query instance.
     */
    public function set($column, $value, $type = null);

    /**
     * Turns the query being built into an insert query that inserts into
     * a certain table
     *
     * <code>
     *     $query
     *         ->from('users')
     *         ->insert(array(
     *             'name'     => 'foo',
     *             'password' => 'bar'
     *         ));
     * </code>
     *
     * @param array $data The values to set.
     *
     * @return int The number of updated rows
     * @throws PrimeException When execute fail
     */
    #[WriteOperation]
    public function insert(array $data = []);

    /**
     * Set insert value
     *
     * <code>
     *     $query
     *         ->from('users')
     *         ->setValue('name', 'foo', 'string')
     *         ->setValue('password', 'bar', 'string')
     *         ->insert();
     * </code>
     *
     * @param string $column
     * @param mixed $value
     * @param mixed $type
     *
     * @return $this
     */
    public function setValue($column, $value, $type = null);

    /**
     * Replace values from mapper table
     *
     * {@see self::insert} for api documentation
     *
     * @param array $values The values to replace.
     *
     * @return int The number of updated rows
     * @throws PrimeException When execute fail
     */
    #[WriteOperation]
    public function replace(array $values = []);

    /**
     * Get rows collection
     *
     * @param array $criteria
     * @param string|array $attributes
     *
     * @return R[]|CollectionInterface<R>|PaginatorInterface<R>
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function find(array $criteria, $attributes = null);

    /**
     * Get one row by criteria
     *
     * @param array $criteria
     * @param string|array $attributes
     *
     * @return R|null
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function findOne(array $criteria, $attributes = null);
}
