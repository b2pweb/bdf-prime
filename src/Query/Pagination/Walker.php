<?php

namespace Bdf\Prime\Query\Pagination;

use BadMethodCallException;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\PrimeSerializable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Pagination\WalkStrategy\PaginationWalkStrategy;
use Bdf\Prime\Query\Pagination\WalkStrategy\WalkCursor;
use Bdf\Prime\Query\Pagination\WalkStrategy\WalkStrategyInterface;
use Bdf\Prime\Query\ReadCommandInterface;
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
 * @author  Seb
 * @package Bdf\Prime\Query\Pagination
 */
class Walker extends PrimeSerializable implements \Iterator, PaginatorInterface
{
    const DEFAULT_PAGE  = 1;
    const DEFAULT_LIMIT = 150;
    
    /**
     * First page
     * 
     * @var int
     */
    protected $startPage;

    /**
     * The current offset
     *
     * @var int
     */
    protected $offset;

    /**
     * @var array
     */
    private $collection = [];

    /**
     * @var WalkStrategyInterface
     */
    private $strategy;

    /**
     * @var WalkCursor
     */
    private $cursor;

    /**
     * @var ReadCommandInterface
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
     * @param ReadCommandInterface $query
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
     * @param WalkStrategyInterface $strategy
     *
     * @return Walker
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
     * @return WalkStrategyInterface
     */
    public function getStrategy(): WalkStrategyInterface
    {
        if ($this->strategy) {
            return $this->strategy;
        }

        return $this->strategy = new PaginationWalkStrategy();
    }

    /**
     * Load the first page of collection
     *
     * @throws PrimeException
     */
    #[ReadOperation]
    public function load()
    {
        $this->page = $this->startPage;
        $this->cursor = $this->getStrategy()->initialize($this->query, $this->maxRows, $this->page);
        $this->loadCollection();
    }

    /**
     * @return ReadCommandInterface
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
        return $this->query->paginationCount();
    }

    /**
     * {@inheritdoc}
     */
    public function order($attribute = null)
    {
        $orders = $this->query()->getOrders();

        if ($attribute === null) {
            return $orders;
        }

        return isset($orders[$attribute]) ? $orders[$attribute] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function limit()
    {
        return $this->cursor->query->getLimit();
    }

    /**
     * {@inheritdoc}
     */
    public function offset()
    {
        return $this->cursor->query->getOffset();
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
    protected function loadCollection()
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
    public function current()
    {
        return current($this->collection);
    }
    
    /**
     * SPL - Iterator
     *
     * {@inheritdoc}
     */
    public function key()
    {
        if ($this->offset !== null) {
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
    public function next()
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
    public function valid()
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
    public function rewind()
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
    public function count()
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
    public function offsetExists($offset)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Collection methods are not supported by the Walker');
    }
}
