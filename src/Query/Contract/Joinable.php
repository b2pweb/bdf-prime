<?php

namespace Bdf\Prime\Query\Contract;

use Closure;

/**
 * Interface for join() methods
 */
interface Joinable
{
    // join type
    const INNER_JOIN = 'INNER';
    const LEFT_JOIN = 'LEFT';
    const RIGHT_JOIN = 'RIGHT';

    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $query
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->join(['phonenumbers', 'p'], 'p.id', '=', 'u.id');
     * </code>
     *
     * @param string|array $table
     * @param string|Closure $key
     * @param string $operator
     * @param string $foreign
     * @param string $type Type of join.
     *
     * @return $this This Query instance.
     */
    public function join($table, $key, $operator = null, $foreign = null, $type = self::INNER_JOIN);

    /**
     * Creates and adds a left join to the query.
     *
     * <code>
     *     $query
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->leftJoin(['phonenumbers', 'p'], 'p.id', '=', 'u.id');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join The table name to join.
     * @param string $alias The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return $this This Query instance.
     */
    public function leftJoin($fromAlias, $join, $alias, $condition = null);

    /**
     * Creates and adds a right join to the query.
     *
     * <code>
     *     $query
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->rightJoin(['phonenumbers', 'p'], 'p.id', '=', 'u.id');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join The table name to join.
     * @param string $alias The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return $this This Query instance.
     */
    public function rightJoin($fromAlias, $join, $alias, $condition = null);
}
