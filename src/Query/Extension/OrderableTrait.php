<?php

namespace Bdf\Prime\Query\Extension;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Contract\Orderable;

/**
 * Trait for @see Orderable
 *
 * @property CompilerInterface $compiler
 * @property array $statements
 */
trait OrderableTrait
{
    /**
     * @see Orderable::order()
     */
    public function order($sort, $order = null)
    {
        $this->compilerState->invalidate('orders');

        $this->statements['orders'] = [];

        if (!is_array($sort)) {
            $sort = [$sort => $order];
        }

        foreach ($sort as $column => $order) {
            if (is_int($column)) {
                $column = $order;
                $order = 'ASC';
            }

            $this->statements['orders'][] = [
                'sort'  => $column,
                'order' => !$order ? 'ASC' : $order,
            ];
        }

        return $this;
    }

    /**
     * @see Orderable::addOrder()
     */
    public function addOrder($sort, $order = null)
    {
        $this->compilerState->invalidate('orders');

        if (!is_array($sort)) {
            $sort = [$sort => $order];
        }

        foreach ($sort as $column => $order) {
            if (is_int($column)) {
                $column = $order;
                $order = 'ASC';
            }

            $this->statements['orders'][] = [
                'sort'  => $column,
                'order' => !$order ? 'ASC' : $order,
            ];
        }

        return $this;
    }

    /**
     * @see Orderable::getOrders()
     */
    public function getOrders()
    {
        $orders = [];

        foreach ($this->statements['orders'] as $part) {
            $orders[$part['sort']] = $part['order'];
        }

        return $orders;
    }
}
