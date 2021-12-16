<?php
namespace Bdf\Prime\Query;

use Bdf\Prime\Query\Expression\ExpressionInterface;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * Interface for Clause (base for queries)
 */
interface ClauseInterface
{
    /**
     * Set custom filters
     *
     * @param array<string,callable(static,mixed):void> $filters
     *
     * @return $this
     */
    public function setCustomFilters(array $filters);

    /**
     * Add a custom filter
     *
     * @param string $name
     * @param callable(static,mixed):void $callback
     *
     * @return $this
     */
    public function addCustomFilter(string $name, callable $callback);

    /**
     * Get custom filters
     *
     * @return array<string,callable(static,mixed):void>
     */
    public function getCustomFilters(): array;

    /**
     * Get clause statement
     *
     * @param string $statement
     *
     * @return array
     */
    public function statement(string $statement): array;

    /**
     * Add clause statement
     *
     * @param string $name
     * @param mixed $values
     *
     * @return void
     */
    public function addStatement(string $name, $values): void;

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
     * @param string $statement
     * @param string|array<string,mixed> $expression The restriction predicates.
     * @param string|null|mixed $operator
     * @param mixed $value
     * @param string $type
     *
     * @return $this
     */
    public function buildClause(string $statement, $expression, $operator = null, $value = null, string $type = CompositeExpression::TYPE_AND);

    /**
     * Add a raw expression in statement
     *
     * <code>
     *    $query->buildRaw('where', 'u.id = 2')
     * </code>
     *
     * @param string $statement
     * @param string|QueryInterface|ExpressionInterface $expression
     * @param string $type
     *
     * @return $this
     */
    public function buildRaw(string $statement, $expression, string $type = CompositeExpression::TYPE_AND);

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
     * @param string $statement
     * @param callable(static):void $callback
     * @param string $type
     *
     * @return $this
     */
    public function buildNested(string $statement, callable $callback, string $type = CompositeExpression::TYPE_AND);

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
    public function addCommand(string $command, $value);
}
