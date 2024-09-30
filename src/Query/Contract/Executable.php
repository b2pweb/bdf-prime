<?php

namespace Bdf\Prime\Query\Contract;

use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Base type for queries that provide execute() wrapper methods
 *
 * @template R as object|array
 */
interface Executable extends SelfExecutable
{
    /**
     * Get all matched data.
     * The all method run the post processors
     *
     * <code>
     *     $query
     *         ->from('users')
     *         ->all('name);
     * </code>
     *
     * @param string|array $columns
     *
     * @return R[]|CollectionInterface<R>
     *
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function all($columns = null);

    /**
     * Get first matched data
     *
     * <code>
     *     $query
     *         ->from('users')
     *         ->first('name);
     * </code>
     *
     * @param string|array $columns
     *
     * @return R|null
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function first($columns = null);

    /**
     * Get a collection of column value
     *
     * <code>
     *     $result = $query
     *         ->from('users')
     *         ->inRows('name');
     *
     *     print_r($result);
     *     // display
     *        Array
     *        (
     *            [0] => 'foo'
     *            [1] => 'bar'
     *             ...
     *
     *      // Expressions can also be used
     *      $query->from('users')->inRows(new Raw('options->>"$.darkMode"'));
     * </code>
     *
     * @param string|ExpressionInterface $column The column to return, or an expression
     *
     * @return list<mixed>
     * @throws PrimeException When execute fail
     *
     * @psalm-suppress MismatchingDocblockParamType
     * @todo Change parameter type hint on prime 3.0
     */
    #[ReadOperation]
    public function inRows(string/*|ExpressionInterface*/ $column): array;

    /**
     * Get a column value
     *
     * <code>
     *     $result = $query
     *         ->from('users')
     *         ->inRow('name');
     *
     *     echo $result;
     *     // display 'foo'
     *
     *     // Expressions can also be used
     *     $query->from('users')->inRow(new Raw('options->>"$.darkMode"'));
     * </code>
     *
     * @param string|ExpressionInterface $column The column to return, or an expression
     *
     * @return mixed
     * @throws PrimeException When execute fail
     *
     * @psalm-suppress MismatchingDocblockParamType
     * @todo Change parameter type hint on prime 3.0
     */
    #[ReadOperation]
    public function inRow(string/*|ExpressionInterface*/ $column);
}
