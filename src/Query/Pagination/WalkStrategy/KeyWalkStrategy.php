<?php

namespace Bdf\Prime\Query\Pagination\WalkStrategy;

use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\Contract\Orderable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\Whereable;
use Bdf\Prime\Query\ReadCommandInterface;
use InvalidArgumentException;

/**
 * Walk strategy using a primary key (or any unique key) as cursor
 * This strategy supports deleting entities during the walk, but the entity must contains a single primary key, and the query must be ordered by this key
 * Any sort on other attribute are not supported
 */
final class KeyWalkStrategy implements WalkStrategyInterface
{
    /**
     * @var KeyInterface
     */
    private $key;

    /**
     * PrimaryKeyWalkStrategy constructor.
     * @param KeyInterface $key
     */
    public function __construct(KeyInterface $key)
    {
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ReadCommandInterface $query, int $chunkSize, int $startPage): WalkCursor
    {
        if (!self::supports($query, $startPage, $this->key->name())) {
            throw new InvalidArgumentException('KeyWalkStrategy is not supported by this query');
        }

        /** @var Limitable&Orderable&ReadCommandInterface $query */
        $query = clone $query;

        if (!isset($query->getOrders()[$this->key->name()])) {
            $query->order($this->key->name(), Orderable::ORDER_ASC);
        }

        $query->limit($chunkSize);

        return new WalkCursor($query);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function next(WalkCursor $cursor): WalkCursor
    {
        $cursor = clone $cursor;

        if ($cursor->entities) {
            $cursor->cursor = $this->key->get(end($cursor->entities));
        }

        if ($cursor->cursor !== null) {
            /** @var ReadCommandInterface&Orderable&Whereable $query */
            $query = $cursor->query;
            $operator = $query->getOrders()[$this->key->name()] === Orderable::ORDER_ASC ? '>' : '<';

            $query->where($this->key->name(), $operator, $cursor->cursor);
        }

        $cursor->entities = $cursor->query->all();

        if ($cursor->entities instanceof CollectionInterface) {
            $cursor->entities = $cursor->entities->all();
        }

        return $cursor;
    }

    /**
     * Check if the strategy supports the given parameters
     *
     * @param ReadCommandInterface $query The query
     * @param int|null $startPage The start page
     * @param string $key The cursor key
     *
     * @return bool
     *
     * @psalm-assert-if-true Orderable&Limitable&Whereable $query
     */
    public static function supports(ReadCommandInterface $query, ?int $startPage, string $key): bool
    {
        if ($startPage !== null && $startPage !== 1) {
            return false;
        }

        if (!($query instanceof Orderable && $query instanceof Limitable && $query instanceof Whereable)) {
            return false;
        }

        $orders = $query->getOrders();

        return empty($orders) || (count($orders) === 1 && isset($orders[$key]));
    }
}
