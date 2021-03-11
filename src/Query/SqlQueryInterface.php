<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\Aggregatable;
use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\Contract\Joinable;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\Contract\Lockable;
use Bdf\Prime\Query\Contract\Orderable;
use Bdf\Prime\Query\Expression\Raw;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * Interface for SQL queries
 */
interface SqlQueryInterface extends QueryInterface, Aggregatable, Limitable, Orderable, Joinable, Lockable, EntityJoinable
{
    /**
     * {@inheritdoc}
     *
     * Gets all defined query bindings for the query being constructed indexed by parameter index or name.
     *
     * @return array The currently defined query parameters indexed by parameter index or name.
     */
    public function getBindings();

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * <code>
     *     $query ->group('u.id'); // GROUP BY u.id
     *     $query ->group('u.id', 'name'); // GROUP BY u.id, name
     * </code>
     *
     * @param mixed $column The grouping expression.
     *
     * @return $this This Query instance.
     */
    public function group($column);

    /**
     * Add a grouping over the results of the query.
     *
     * <code>
     *     $query ->addGroup('u.id'); // GROUP BY u.id
     *     $query ->addGroup('u.date', 'name'); // GROUP BY u.id, u.date, name
     * </code>
     *
     * @param mixed $column The grouping expression.
     *
     * @return $this This Query instance.
     */
    public function addGroup($column);

    /**
     * Specifies a restriction over the groups of the query.
     * Replaces any previous having restrictions, if any.
     *
     * @param  string|array $column The restriction predicates.
     * @param  string $operator
     * @param  mixed $value
     *
     * @return $this This Query instance.
     *
     * @see where()
     */
    public function having($column, $operator = null, $value = null);

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * disjunction with any existing having restrictions.
     *
     * @param  string|array $column The restriction predicates.
     * @param  string $operator
     * @param  mixed $value
     *
     * @return $this This Query instance.
     *
     * @see having()
     */
    public function orHaving($column, $operator = null, $value = null);

    /**
     * Add having IS NULL expression
     *
     * @param string $column
     * @param string $type
     *
     * @return $this This Query instance.
     */
    public function havingNull($column, $type = CompositeExpression::TYPE_AND);

    /**
     * Add having IS NOT NULL expression
     *
     * @param string $column
     * @param string $type
     *
     * @return $this This Query instance.
     */
    public function havingNotNull($column, $type = CompositeExpression::TYPE_AND);

    /**
     * Add OR having IS NULL expression
     *
     * @param string $column
     *
     * @return $this This Query instance.
     */
    public function orHavingNull($column);

    /**
     * Add OR having IS NOT NULL expression
     *
     * @param string $column
     *
     * @return $this This Query instance.
     */
    public function orHavingNotNull($column);

    /**
     * Add having SQL expression
     *
     * @param string $raw
     * @param string $type
     *
     * @return $this This Query instance.
     */
    public function havingRaw($raw, $type = CompositeExpression::TYPE_AND);

    /**
     * Add OR having SQL expression
     *
     * @param string $raw
     *
     * @return $this This Query instance.
     */
    public function orHavingRaw($raw);

    /**
     * Add a raw query
     *
     * @param string $sql
     *
     * @return Raw
     */
    public function raw($sql);

    /**
     * Add key word IGNORE on insert
     *
     * <code>
     *     $query
     *         ->from('users')
     *         ->setValue('password', 'bar', 'string')
     *         ->ignore()
     *         ->insert();
     * </code>
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function ignore($flag = true);

    /**
     * Add key word DISTINCT on select
     *
     * <code>
     *     $query
     *         ->select('u.id')
     *         ->distinct()
     *         ->from('users', 'u');
     * </code>
     *
     * @param bool $flag
     *
     * @return $this This Query instance.
     */
    public function distinct($flag = true);

    /**
     * Quote a value
     *
     * @param string $value
     * @param int $type
     *
     * @return string
     * @throws PrimeException
     */
    public function quote($value, $type = null);

    /**
     * Quote a identifier
     *
     * @param string $column
     *
     * @return string
     * @throws PrimeException
     */
    public function quoteIdentifier($column);

    /**
     * Get the count of the query for pagination
     *
     * @param string|null $column
     *
     * @return int
     * @throws PrimeException When execute fail
     */
    public function paginationCount(?string $column = null): int;

    /**
     * Gets the complete SQL string formed by the current specifications of this query.
     *
     * <code>
     *     $query
     *         ->select('*')
     *         ->from('User', 'u')
     *     echo $query->toSql(); // SELECT * FROM User u
     * </code>
     *
     * @return string The SQL query string.
     * @throws PrimeException When compilation fail
     */
    public function toSql();

    /**
     * @todo A reprendre: utiliser les types des bindings
     * @throws PrimeException When compilation fail
     */
    public function toRawSql();
}
