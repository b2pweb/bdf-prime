<?php

namespace Bdf\Prime\Query\Contract;

use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * Interface for where() method
 *
 * @method $this whereReplace(string $column, $operator = null, $value = null) Add or replace single where criterion.
 */
interface Whereable
{
    /**
     * Specifies one or more restrictions to the query result.
     *
     * <code>
     *     $query
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->where('u.id = 1')  // RAW sql
     *         ->where('u.id', '=', '1') // interpreted expression: will replace by positionnal char
     *         ->where('u.id', '1')  // default operator is '='
     *         ->where(['u.id' => '1']);  // send criteria
     *
     *     // You can build nested expressions
     *    $query
     *         ->where(function($query) {
     *             $query->where(['id :like' => '123%']);
     *         })
     * </code>
     *
     * @param string|array<string,mixed>|callable(static):void $column The restriction predicates.
     * @param string|mixed|null $operator The comparison operator, or the value is you want to use "=" operator
     * @param mixed $value
     *
     * @return $this This Query instance.
     */
    public function where($column, $operator = null, $value = null);

    /**
     * Add or replace single where criterion
     * The criterion value will be replaced on the first occurrence matching with the column and the operator
     *
     * Note: Unlike the where() method, this method does not support the array syntax nor nested statements
     *
     * <code>
     *     $query
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->whereReplace('u.id', '1') // Filter not existing, will be added
     *     ;
     *
     *     $query->whereReplace('u.id', '2'); // Will replace the previous where
     *     $query->whereReplace('u.id', '<', '1000'); // Operator is different, so the clause is added
     * </code>
     *
     * @param string $column The column name to filter
     * @param string|mixed|null $operator The comparison operator, or the value is you want to use "=" operator
     * @param mixed $value
     *
     * @return $this This Query instance.
     */
    //public function whereReplace(string $column, $operator = null, $value = null); // @todo uncomment on prime 3.0

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * disjunction with any previously specified restrictions.
     *
     * <code>
     *     $query
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->where('u.id = 1')
     *         ->orWhere('u.id = 2');
     * </code>
     *
     * @param string|array<string,mixed>|callable(static):void $column The restriction predicates.
     * @param string|mixed|null $operator The comparison operator, or the value is you want to use "=" operator
     * @param mixed $value
     *
     * @return $this This Query instance.
     *
     * @see where()
     */
    public function orWhere($column, $operator = null, $value = null);

    /**
     * Add where IS NULL expression
     *
     * @param string $column
     * @param string $type
     *
     * @return $this This Query instance.
     */
    public function whereNull(string $column, string $type = CompositeExpression::TYPE_AND);

    /**
     * Add where IS NOT NULL expression
     *
     * @param string $column
     * @param string $type
     *
     * @return $this This Query instance.
     */
    public function whereNotNull(string $column, string $type = CompositeExpression::TYPE_AND);

    /**
     * Add OR where IS NULL expression
     *
     * @param string $column
     *
     * @return $this This Query instance.
     */
    public function orWhereNull(string $column);

    /**
     * Add OR where IS NOT NULL expression
     *
     * @param string $column
     *
     * @return $this This Query instance.
     */
    public function orWhereNotNull(string $column);

    /**
     * Add where SQL expression
     *
     * @param string|\Bdf\Prime\Query\QueryInterface|\Bdf\Prime\Query\Expression\ExpressionInterface $raw
     * @param string $type
     *
     * @return $this This Query instance.
     */
    public function whereRaw($raw, string $type = CompositeExpression::TYPE_AND);

    /**
     * Add OR where SQL expression
     *
     * @param string|\Bdf\Prime\Query\QueryInterface|\Bdf\Prime\Query\Expression\ExpressionInterface $raw
     *
     * @return $this This Query instance.
     */
    public function orWhereRaw($raw);

    /**
     * Add where nested
     *
     * <code>
     *    $query
     *         ->nested(function($query) {
     *             $query->where(['id :like' => '123%']);
     *         })
     * </code>
     *
     * @param callable(static):void $callback
     * @param string $type
     *
     * @return $this This Query instance.
     */
    public function nested(callable $callback, string $type = CompositeExpression::TYPE_AND);
}
