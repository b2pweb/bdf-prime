<?php

namespace Bdf\Prime\Query\Pagination;

use BadMethodCallException;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\PrimeSerializable;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\Contract\Orderable;
use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Pagination\WalkStrategy\PaginationWalkStrategy;
use Bdf\Prime\Query\Pagination\WalkStrategy\WalkCursor;
use Bdf\Prime\Query\Pagination\WalkStrategy\WalkStrategyInterface;
use Bdf\Prime\Query\ReadCommandInterface;
use Iterator;
use LogicException;

/**
 * Query Walker
 *
 * Permet de parcourir des collections contenant de gros volume d'entités.
 * Le parcourt se fait par paquet d'entités définis par la limit de la query
 * Une fois la limite atteinte, la classe lance la requête suivante
 *
 * Attention, le walker ne gère pas les objects collection
 *
 * @template R as array|object
 *
 * @implements PaginatorInterface<R>
 * @implements Iterator<array-key, R>
 */
class Walker extends PrimeSerializable implements Iterator, PaginatorInterface
{
    public const DEFAULT_PAGE  = 1;
    public const DEFAULT_LIMIT = 150;

    /**
     * First page
     *
     * @var int
     */
    protected $startPage;

    /**
     * The current offset
     *
     * @var int|null
     */
    protected $offset;

    /**
     * @var R[]
     */
    private $collection = [];

    /**
     * @var WalkStrategyInterface<R>
     */
    private $strategy;

    /**
     * @var WalkCursor<R>
     */
    private $cursor;

    /**
     * @var ReadCommandInterface<ConnectionInterface, R>
     */
    private $query;

    /**
     * @var int
     */
    private $page;

    /**
     * @var int
     */
    private $maxRows;

    /**
     * Create a query walker
     *
     * @param ReadCommandInterface<ConnectionInterface, R> $query
     * @param int            $maxRows
     * @param int            $page
     */
    public function __construct(ReadCommandInterface $query, $maxRows = null, $page = null)
    {
        $this->query = $query;
        $this->page = 0;
        $this->maxRows = $maxRows ?: self::DEFAULT_LIMIT;
        $this->startPage = $page ?: self::DEFAULT_PAGE;
    }

    /**
     * Change the walk strategy
     *
     * @param WalkStrategyInterface<R> $strategy
     *
     * @return $this
     */
    public function setStrategy(WalkStrategyInterface $strategy): self
    {
        if ($this->cursor !== null) {
            throw new LogicException('Cannot change walk strategy during walk');
        }

        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Get the current active walk strategy
     *
     * @return WalkStrategyInterface<R>
     */
    public function getStrategy(): WalkStrategyInterface
    {
        if ($this->strategy) {
            return $this->strategy;
        }

        /**
         * @var WalkStrategyInterface<R>
         * @psalm-suppress InvalidPropertyAssignmentValue
         */
        return $this->strategy = new PaginationWalkStrategy();
    }

    /**
     * Load the first page of collection
     *
     * @throws PrimeException
     *
     * @return void
     */
    #[ReadOperation]
    public function load(): void
    {
        $this->page = $this->startPage;
        $this->cursor = $this->getStrategy()->initialize($this->query, $this->maxRows, $this->page);
        $this->loadCollection();
    }

    /**
     * @return ReadCommandInterface<ConnectionInterface, R>
     */
    public function query(): ReadCommandInterface
    {
        if ($this->cursor) {
            return $this->cursor->query;
        }

        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function collection()
    {
        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function size()
    {
        if (!$this->query instanceof Paginable) {
            throw new BadMethodCallException(__METHOD__.' should be called with a Paginable query');
        }

        return $this->query->paginationCount();
    }

    /**
     * {@inheritdoc}
     */
    public function order($attribute = null)
    {
        $query = $this->query();

        if (!$query instanceof Orderable) {
            return $attribute ? null : [];
        }

        $orders = $query->getOrders();

        if ($attribute === null) {
            return $orders;
        }

        return $orders[$attribute] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function limit(): ?int
    {
        if ($this->cursor->query instanceof Limitable) {
            return $this->cursor->query->getLimit();
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function offset(): ?int
    {
        if ($this->cursor->query instanceof Limitable) {
            return $this->cursor->query->getOffset();
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function page()
    {
        return $this->page;
    }

    /**
     * {@inheritdoc}
     */
    public function pageMaxRows()
    {
        return $this->maxRows;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadCollection(): void
    {
        $this->cursor = $this->strategy->next($this->cursor);
        $this->collection = $this->cursor->entities;

        // Test if the collection has numerical keys.
        // We have to add the offset to the numerical key.
        if (isset($this->collection[0])) {
            $this->offset = ($this->page - $this->startPage) * $this->maxRows;
        } else {
            $this->offset = null;
        }
    }

    /**
     * SPL - Iterator
     *
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->collection);
    }

    /**
     * SPL - Iterator
     *
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        if ($this->offset !== null) {
            /** @var array<int, mixed> $this->collection */
            return $this->offset + key($this->collection);
        }

        return key($this->collection);
    }

    /**
     * SPL - Iterator
     *
     * {@inheritdoc}
     *
     * @throws PrimeException
     */
    #[ReadOperation]
    public function next(): void
    {
        if (false === next($this->collection)) {
            $this->page++;
            $this->loadCollection();
        }
    }

    /**
     * SPL - Iterator
     *
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return false !== current($this->collection);
    }

    /**
     * SPL - Iterator
     *
     * {@inheritdoc}
     *
     * @throws PrimeException
     */
    #[ReadOperation]
    public function rewind(): void
    {
        if (($this->page == $this->startPage) && count($this->collection)) {
            reset($this->collection);
        } else {
            $this->load();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->collection);
    }

    /**
     * {@inheritdoc}
     */
    public function pushAll(array $items)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function push($item)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function put($key, $item)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function map($callback)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function filter($callback = null)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy($groupBy, $mode = self::GROUPBY)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function indexOf($value, $strict = false)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function merge($items)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function sort(callable $callback = null)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }
}
