<?php

namespace Bdf\Prime\Query\Contract;

use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Interface for sortable (order) queries
 */
interface Orderable
{
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * <code>
     *     $query->order('u.id'); // ORDER BY u.id ASC
     *     $query->order(['u.id', 'name']); // ORDER BY u.id ASC, name ASC
     *     $query->order(['u.id' => 'asc', 'name' => 'desc']); // ORDER BY u.id asc, name desc
     * </code>
     *
     * @param string|array|ExpressionInterface $sort The ordering expression.
     * @param string|null $order The ordering direction.
     *
     * @return $this This Query instance.
     */
    public function order($sort, $order = null);

    /**
     * Adds an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * <code>
     *     $query ->addOrder('id'); // ORDER BY id ASC
     *     $query ->addOrder('date', 'name'); // GROUP BY id ASC, date ASC, name ASC
     * </code>
     *
     * @param string|array|ExpressionInterface $sort The ordering expression.
     * @param string|null $order The ordering direction.
     *
     * @return $this This Query instance.
     */
    public function addOrder($sort, $order = null);

    /**
     * Get orders
     *
     * @return array
     */
    public function getOrders();
}
