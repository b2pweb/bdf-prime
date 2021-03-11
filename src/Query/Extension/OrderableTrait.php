<?php

namespace Bdf\Prime\Query\Extension;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Contract\Orderable;

/**
 * Trait for @see Orderable
 *
 * @property CompilerInterface $compiler
 * @property array $statements
 *
 * @psalm-require-implements Orderable
 */
trait OrderableTrait
{
    /**
     * {@inheritdoc}
     *
     * @see Orderable::order()
     */
    public function order($sort, ?string $order = null)
    {
        $this->compilerState->invalidate('orders');

        $this->statements['orders'] = [];

        if (!is_array($sort)) {
            $sort = [$sort => $order];
        }

        foreach ($sort as $column => $order) {
            if (is_int($column)) {
                $column = $order;
                $order = Orderable::ORDER_ASC;
            }

            $this->statements['orders'][] = [
                'sort'  => $column,
                'order' => !$order ? Orderable::ORDER_ASC : $order,
            ];
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see Orderable::addOrder()
     */
    public function addOrder($sort, ?string $order = null)
    {
        $this->compilerState->invalidate('orders');

        if (!is_array($sort)) {
            $sort = [$sort => $order];
        }

        foreach ($sort as $column => $order) {
            if (is_int($column)) {
                $column = $order;
                $order = Orderable::ORDER_ASC;
            }

            $this->statements['orders'][] = [
                'sort'  => $column,
                'order' => !$order ? Orderable::ORDER_ASC : $order,
            ];
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see Orderable::getOrders()
     */
    public function getOrders(): array
    {
        $orders = [];

        foreach ($this->statements['orders'] as $part) {
            $orders[$part['sort']] = $part['order'];
        }

        return $orders;
    }
}
