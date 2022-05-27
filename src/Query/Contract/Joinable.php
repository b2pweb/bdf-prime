<?php

namespace Bdf\Prime\Query\Contract;

/**
 * Interface for join() methods
 */
interface Joinable
{
    // join type
    public const INNER_JOIN = 'INNER';
    public const LEFT_JOIN = 'LEFT';
    public const RIGHT_JOIN = 'RIGHT';

    /**
     * Creates and adds a join to the query.
     *
     * Simple usage:
     * <code>
     *     $query
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->join(['phonenumbers', 'p'], 'p.id', '=', new Attribute('u.id'));
     * </code>
     *
     * With subQuery:
     * <code>
     *     $subQuery = MyEntity::builder()->select(['bar' => 'foo'])->where(...);
     *     $query
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->join([$subQuery, 's'], 's.bar', '=', new Attribute('u.id'));
     * </code>
     *
     * @param string|\Bdf\Prime\Query\QueryInterface|array $table The joined table. Can also be a sub query. To defined an alias, use syntax [$table, $alias]
     * @param string|callable(\Bdf\Prime\Query\JoinClause):void $key The local key (fk), or the join clause configurator
     * @param string|null $operator If $key is a string, the matching operator
     * @param mixed|\Bdf\Prime\Query\Expression\ExpressionInterface|null $foreign If $key is a string, the foreign key value. Use new Attribute() to match with an attribute
     * @param Joinable::* $type Type of join.
     *
     * @return $this This Query instance.
     */
    public function join($table, $key, ?string $operator = null, $foreign = null, string $type = self::INNER_JOIN);

    /**
     * Creates and adds a left join to the query.
     * This is equivalent to call join() with type LEFT_JOIN as last parameter
     *
     * Simple usage:
     * <code>
     *     $query
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->leftJoin(['phonenumbers', 'p'], 'p.id', '=', new Attribute('u.id'));
     * </code>
     *
     * With subQuery:
     * <code>
     *     $subQuery = MyEntity::builder()->select(['bar' => 'foo'])->where(...);
     *     $query
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->leftJoin([$subQuery, 's'], 's.bar', '=', new Attribute('u.id'));
     * </code>
     *
     * @param string|\Bdf\Prime\Query\QueryInterface|array $table The joined table. Can also be a sub query. To defined an alias, use syntax [$table, $alias]
     * @param string|callable(\Bdf\Prime\Query\JoinClause):void $key The local key (fk), or the join clause configurator
     * @param string|null $operator If $key is a string, the matching operator
     * @param mixed|\Bdf\Prime\Query\Expression\ExpressionInterface|null $foreign If $key is a string, the foreign key value. Use new Attribute() to match with an attribute
     *
     * @return $this This Query instance.
     */
    public function leftJoin($table, $key, ?string $operator = null, $foreign = null);

    /**
     * Creates and adds a right join to the query.
     * This is equivalent to call join() with type RIGHT_JOIN as last parameter
     *
     * Simple usage:
     * <code>
     *     $query
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->leftJoin(['phonenumbers', 'p'], 'p.id', '=', new Attribute('u.id'));
     * </code>
     *
     * With subQuery:
     * <code>
     *     $subQuery = MyEntity::builder()->select(['bar' => 'foo'])->where(...);
     *     $query
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->leftJoin([$subQuery, 's'], 's.bar', '=', new Attribute('u.id'));
     * </code>
     *
     * @param string|\Bdf\Prime\Query\QueryInterface|array $table The joined table. Can also be a sub query. To defined an alias, use syntax [$table, $alias]
     * @param string|callable(\Bdf\Prime\Query\JoinClause):void $key The local key (fk), or the join clause configurator
     * @param string|null $operator If $key is a string, the matching operator
     * @param mixed|\Bdf\Prime\Query\Expression\ExpressionInterface|null $foreign If $key is a string, the foreign key value. Use new Attribute() to match with an attribute
     *
     * @return $this This Query instance.
     */
    public function rightJoin($table, $key, ?string $operator = null, $foreign = null);
}
