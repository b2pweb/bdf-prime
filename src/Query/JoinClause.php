<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Query\Expression\ExpressionInterface;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * JoinClause
 *
 * @package Bdf\Prime\Query
 */
final class JoinClause extends Clause
{
    /**
     * Add an "on" clause to the join.
     *
     * On clauses can be chained, e.g.
     *
     *  $join->on('contacts.user_id', '=', 'users.id')
     *       ->on('contacts.info_id', '=', 'info.id')
     *
     * will produce the following SQL:
     *
     * on `contacts`.`user_id` = `users`.`id`  and `contacts`.`info_id` = `info`.`id`
     *
     * @param \Closure|string $key
     * @param mixed $operator The comparison operator, or the value if $foreign is not provided
     * @param string|ExpressionInterface|null $foreign The comparison value
     *
     * @return $this
     */
    public function on(\Closure|string $key, mixed $operator = null, mixed $foreign = null): self
    {
        if ($key instanceof \Closure) {
            $this->nested($key, $operator ?: CompositeExpression::TYPE_AND);
        } else {
            $this->buildClause('on', $key, $operator, $foreign);
        }

        return $this;
    }

    /**
     * Add an "or on" clause to the join.
     *
     * @param \Closure|string $key
     * @param mixed $operator The comparison operator, or the value if $foreign is not provided
     * @param string|ExpressionInterface|null $foreign The comparison value
     *
     * @return $this
     */
    public function orOn(\Closure|string $key, mixed $operator = null, mixed $foreign = null): self
    {
        if ($key instanceof \Closure) {
            $this->nested($key, $operator ?: CompositeExpression::TYPE_OR);
        } else {
            $this->buildClause('on', $key, $operator, $foreign, CompositeExpression::TYPE_OR);
        }

        return $this;
    }

    /**
     * Add on IS NULL expression
     *
     * @param string $column
     * @param string $type
     *
     * @return $this This Query instance.
     */
    public function onNull(string $column, string $type = CompositeExpression::TYPE_AND): self
    {
        return $this->buildClause('on', $column, '=', null, $type);
    }

    /**
     * Add on IS NOT NULL expression
     *
     * @param string $column
     * @param string $type
     *
     * @return $this This Query instance.
     */
    public function onNotNull(string $column, string $type = CompositeExpression::TYPE_AND): self
    {
        return $this->buildClause('on', $column, '!=', null, $type);
    }

    /**
     * Add OR on IS NULL expression
     *
     * @param string $column
     *
     * @return $this This Query instance.
     */
    public function orOnNull(string $column): self
    {
        return $this->onNull($column, CompositeExpression::TYPE_OR);
    }

    /**
     * Add OR on IS NOT NULL expression
     *
     * @param string $column
     *
     * @return $this This Query instance.
     */
    public function orOnNotNull(string $column): self
    {
        return $this->onNotNull($column, CompositeExpression::TYPE_OR);
    }

    /**
     * Add on SQL expression
     *
     * @param string|QueryInterface|ExpressionInterface $raw
     * @param string $type
     *
     * @return $this This Query instance.
     */
    public function onRaw(string|QueryInterface|ExpressionInterface $raw, string $type = CompositeExpression::TYPE_AND): self
    {
        return $this->buildRaw('on', $raw, $type);
    }

    /**
     * Add OR on SQL expression
     *
     * @param string|QueryInterface|ExpressionInterface $raw
     *
     * @return $this This Query instance.
     */
    public function orOnRaw(string|QueryInterface|ExpressionInterface $raw): self
    {
        return $this->onRaw($raw, CompositeExpression::TYPE_OR);
    }

    /**
     * Add on nested
     *
     * @param \Closure $callback
     * @param string   $type
     *
     * @return $this This Query instance.
     */
    public function nested(\Closure $callback, string $type = CompositeExpression::TYPE_AND): self
    {
        return $this->buildNested('on', $callback, $type);
    }

    /**
     * Get the on statement
     *
     * @return array
     */
    public function clauses(): array
    {
        return $this->statement('on');
    }
}
