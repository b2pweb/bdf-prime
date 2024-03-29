<?php

namespace Bdf\Prime\Query\Pagination\WalkStrategy;

use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\Clause;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\Contract\Orderable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\Whereable;
use Bdf\Prime\Query\ReadCommandInterface;
use InvalidArgumentException;

use function method_exists;

/**
 * Walk strategy using a primary key (or any unique key) as cursor
 * This strategy supports deleting entities during the walk, but the entity must contains a single primary key, and the query must be ordered by this key
 * Any sort on other attribute are not supported
 *
 * @template E as object
 * @implements WalkStrategyInterface<E>
 */
final class KeyWalkStrategy implements WalkStrategyInterface
{
    /**
     * @var KeyInterface<E>
     */
    private $key;

    /**
     * PrimaryKeyWalkStrategy constructor.
     *
     * @param KeyInterface<E> $key
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

        /** @var Limitable&Orderable&ReadCommandInterface<ConnectionInterface, E> $query */
        $query = clone $query;

        if (!isset($query->getOrders()[$this->key->name()])) {
            $query->order($this->key->name(), Orderable::ORDER_ASC);
        }

        $query->limit($chunkSize);

        /** @var WalkCursor<E> */
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
            $cursor->cursor = $this->getLastKeyOfEntities($cursor);
        }

        if ($cursor->cursor !== null) {
            /** @var ReadCommandInterface<ConnectionInterface, E>&Orderable&Whereable $query */
            $query = $cursor->query;
            $column = $this->key->name();
            $operator = $query->getOrders()[$column] === Orderable::ORDER_ASC ? '>' : '<';

            // #FRAM-86 : reset where clause
            // @todo remove method_exists check on prime 3.0
            if (method_exists($query, 'whereReplace')) {
                $query->whereReplace($column, $operator, $cursor->cursor);
            } else {
                $query->where($column, $operator, $cursor->cursor);
            }
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

    /**
     * Find the last key to be set as cursor value
     *
     * @param WalkCursor $cursor
     * @return mixed
     *
     * @see WalkCursor::$cursor
     */
    private function getLastKeyOfEntities(WalkCursor $cursor)
    {
        $lastEntity = end($cursor->entities);

        // Basic select query : results are an ordered list, so the last key is always the key of the last entity
        if (array_is_list($cursor->entities)) {
            return $this->key->get($lastEntity);
        }

        // group by query
        // Because index can be overridden (or value are added), order is not guaranteed
        // So we should iterate other entities to find the "max" key
        // In case of "by combine", values of each key are ordered list, so we simply need to take the last entity's key of each index
        $lastKey = $this->key->get(is_array($lastEntity) ? end($lastEntity) : $lastEntity);

        /** @var ReadCommandInterface<ConnectionInterface, E>&Orderable&Whereable $query */
        $query = $cursor->query;
        $asc = $query->getOrders()[$this->key->name()] === Orderable::ORDER_ASC;

        foreach ($cursor->entities as $entity) {
            $key = $this->key->get(is_array($entity) ? end($entity) : $entity);
            $gt = $key > $lastKey;

            // order is ascendant and key is > lastKey
            // or order is descendant and key is < lastKey
            if ($asc === $gt) {
                $lastKey = $key;
            }
        }

        return $lastKey;
    }
}
