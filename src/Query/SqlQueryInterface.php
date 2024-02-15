<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\Aggregatable;
use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\Contract\Joinable;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\Contract\Lockable;
use Bdf\Prime\Query\Contract\Orderable;
use Bdf\Prime\Query\Contract\SqlCompilable;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * Interface for SQL queries
 *
 * @template C as \Bdf\Prime\Connection\ConnectionInterface
 * @template R as object|array
 *
 * @extends QueryInterface<C, R>
 */
interface SqlQueryInterface extends QueryInterface, Aggregatable, Limitable, Orderable, Joinable, Lockable, EntityJoinable, SqlCompilable
{
    /**
     * {@inheritdoc}
     *
     * Gets all defined query bindings for the query being constructed indexed by parameter index or name.
     *
     * @return array The currently defined query parameters indexed by parameter index or name.
     */
    public function getBindings(): array;

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * <code>
     *     $query->group('u.id'); // GROUP BY u.id
     *     $query->group('u.id', 'name'); // GROUP BY u.id, name
     * </code>
     *
     * @param string ...$columns The grouping columns.
     *
     * @return $this This Query instance.
     */
    public function group(string ...$columns);

    /**
     * Add a grouping over the results of the query.
     *
     * <code>
     *     $query->addGroup('u.id'); // GROUP BY u.id
     *     $query->addGroup('u.date', 'name'); // GROUP BY u.id, u.date, name
     * </code>
     *
     * @param string ...$columns The grouping columns.
     *
     * @return $this This Query instance.
     * @no-named-arguments
     */
    public function addGroup(string ...$columns);

    /**
     * Specifies a restriction over the groups of the query.
     * Replaces any previous having restrictions, if any.
     *
     * @param string|array<string,mixed>|callable(static):void $column The restriction predicates.
     * @param string|mixed|null $operator The comparison operator, or the value is you want to use "=" operator
     * @param mixed $value
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
     * @param string|array<string,mixed>|callable(static):void $column The restriction predicates.
     * @param string|mixed|null $operator The comparison operator, or the value is you want to use "=" operator
     * @param mixed $value
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
    public function havingNull(string $column, string $type = CompositeExpression::TYPE_AND);

    /**
     * Add having IS NOT NULL expression
     *
     * @param string $column
     * @param string $type
     *
     * @return $this This Query instance.
     */
    public function havingNotNull(string $column, string $type = CompositeExpression::TYPE_AND);

    /**
     * Add OR having IS NULL expression
     *
     * @param string $column
     *
     * @return $this This Query instance.
     */
    public function orHavingNull(string $column);

    /**
     * Add OR having IS NOT NULL expression
     *
     * @param string $column
     *
     * @return $this This Query instance.
     */
    public function orHavingNotNull(string $column);

    /**
     * Add having SQL expression
     *
     * @param string|\Bdf\Prime\Query\QueryInterface|\Bdf\Prime\Query\Expression\ExpressionInterface $raw
     * @param string $type
     *
     * @return $this This Query instance.
     */
    public function havingRaw($raw, string $type = CompositeExpression::TYPE_AND);

    /**
     * Add OR having SQL expression
     *
     * @param string|\Bdf\Prime\Query\QueryInterface|\Bdf\Prime\Query\Expression\ExpressionInterface $raw
     *
     * @return $this This Query instance.
     */
    public function orHavingRaw($raw);

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
    public function ignore(bool $flag = true);

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
    public function distinct(bool $flag = true);

    /**
     * Quote a value
     *
     * @param scalar $value
     * @param \Doctrine\DBAL\ParameterType::* $type
     *
     * @return string
     * @throws PrimeException
     */
    public function quote($value, int $type = null): string;

    /**
     * Quote a identifier
     *
     * @param string $column
     *
     * @return string
     * @throws PrimeException
     */
    public function quoteIdentifier(string $column): string;

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
    public function toSql(): string;

    /**
     * Return SQL representation of the query with bindings
     * Works like `toSql()` but replace placeholders by bound values
     *
     * @return string
     *
     * @todo A reprendre: utiliser les types des bindings
     * @throws PrimeException When compilation fail
     */
    public function toRawSql(): string;
}
