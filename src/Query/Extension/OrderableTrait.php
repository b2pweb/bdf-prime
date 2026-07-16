<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Contract\Orderable;
use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Trait for @see Orderable
 *
 * @property array{orders:array<array{sort:string,order:Orderable::ORDER_*}>} $statements
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
    public function order(string|array|ExpressionInterface $sort, ?string $order = null): static
    {
        $this->statements['orders'] = [];

        return $this->addOrder($sort, $order);
    }

    /**
     * {@inheritdoc}
     *
     * @see Orderable::addOrder()
     */
    public function addOrder(string|array|ExpressionInterface $sort, ?string $order = null): static
    {
        $this->compilerState->invalidate('orders');

        if (!is_array($sort)) {
            $this->statements['orders'][] = [
                'sort'  => $sort,
                'order' => !$order ? Orderable::ORDER_ASC : $order,
            ];

            return $this;
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
     * @return array<string, Orderable::ORDER_*>
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
