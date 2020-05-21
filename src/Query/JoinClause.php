<?php

namespace Bdf\Prime\Query;

use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * JoinClause
 * 
 * @package Bdf\Prime\Query
 */
class JoinClause extends Clause
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
     * @param  \Closure|string  $key
     * @param  string|null      $operator
     * @param  string|null      $foreign
     * 
     * @return $this
     */
    public function on($key, $operator = null, $foreign = null)
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
     * @param  \Closure|string  $key
     * @param  string|null      $operator
     * @param  string|null      $foreign
     * 
     * @return $this
     */
    public function orOn($key, $operator = null, $foreign = null)
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
    public function onNull($column, $type = CompositeExpression::TYPE_AND)
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
    public function onNotNull($column, $type = CompositeExpression::TYPE_AND)
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
    public function orOnNull($column)
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
    public function orOnNotNull($column)
    {
        return $this->onNotNull($column, CompositeExpression::TYPE_OR);
    }

    /**
     * Add on SQL expression
     *
     * @param string $raw
     * @param string $type
     *
     * @return $this This Query instance.
     */
    public function onRaw($raw, $type = CompositeExpression::TYPE_AND)
    {
        return $this->buildRaw('on', $raw, $type);
    }

    /**
     * Add OR on SQL expression
     *
     * @param string $raw
     *
     * @return $this This Query instance.
     */
    public function orOnRaw($raw)
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
    public function nested(\Closure $callback, $type = CompositeExpression::TYPE_AND)
    {
        return $this->buildNested('on', $callback, $type);
    }
    
    /**
     * Get the on statement
     * 
     * @return array
     */
    public function clauses()
    {
        return $this->statement('on');
    }
}