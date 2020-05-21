<?php
namespace Bdf\Prime\Query;

use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * Interface for Clause (base for queries)
 */
interface ClauseInterface
{
    /**
     * Set custom filters
     *
     * @param array $filters
     *
     * @return $this
     */
    public function setCustomFilters(array $filters);

    /**
     * Add a custom filter
     *
     * @param string $name
     * @param \Closure $callback
     *
     * @return $this
     */
    public function addCustomFilter($name, \Closure $callback);

    /**
     * Get custom filters
     *
     * @return array
     */
    public function getCustomFilters();

    /**
     * Get clause statement
     *
     * @param string $statement
     *
     * @return array
     */
    public function statement($statement);

    /**
     * Add clause statement
     *
     * @param string $name
     * @param mixed $values
     *
     * @return array
     */
    public function addStatement($name, $values);

    /**
     * Add a criteria part: WHERE HAVING ON
     *
     * 2 distinct calls:
     *   * without array
     *   * with array
     *
     * <code>
     *     $query
     *         ->buildClause('where', 'u.id')  // IS NULL shortcut
     *         ->buildClause('where', 'u.id', '=', '1') // interpreted expression: will replace by positionnal char
     *         ->buildClause('where', 'u.id', '1')  // default operator is '='
     *         ->buildClause('where', ['u.id' => '1']);  // send criteria
     *         ->buildClause('where', ['u.id =' => '1']);  // send criteria with operator
     * </code>
     *
     * Does not manage expression with ExpressionInterface
     * <code>
     * // Not working
     *     $query->buildClause('where', new Raw('raw expression'));
     * // Do
     *     $query->buildRaw('where', new Raw('raw expression'));
     *     // Or
     *     $query->buildRaw('where', 'raw expression');
     * </code>
     *
     * @param  string $statement
     * @param  string|array $expression The restriction predicates.
     * @param  string $operator
     * @param  mixed $value
     * @param  string $type
     *
     * @return $this
     */
    public function buildClause($statement, $expression, $operator = null, $value = null, $type = CompositeExpression::TYPE_AND);

    /**
     * Add a raw expression in statement
     *
     * <code>
     *    $query->buildRaw('where', 'u.id = 2')
     * </code>
     *
     * @param string $statement
     * @param string $expression
     * @param string $type
     *
     * @return $this
     */
    public function buildRaw($statement, $expression, $type = CompositeExpression::TYPE_AND);

    /**
     * Add nested statement
     *
     * <code>
     *    $query
     *         ->buildNested('where', function($query) {
     *             ...
     *         })
     * </code>
     *
     * @param  string $statement
     * @param \Closure $callback
     * @param string $type
     *
     * @return $this
     */
    public function buildNested($statement, \Closure $callback, $type = CompositeExpression::TYPE_AND);

    /**
     * @todo Revoir cette gestion des commandes
     *
     * Call method from command available in criteria
     *
     * @param string $command
     * @param mixed $value
     *
     * @return $this This Query instance.
     */
    public function addCommand($command, $value);
}