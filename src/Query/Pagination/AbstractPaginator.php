<?php

namespace Bdf\Prime\Query\Pagination;

use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\PrimeSerializable;
use Bdf\Prime\Query\QueryInterface;

/**
 * Abstract paginator
 * 
 * Provide method for pagination
 * 
 * @author  Seb
 * @package Bdf\Prime\Query\Pagination
 * 
 * 
 * @todo retourner une nouvelle instance du paginator sur les methodes de collection ?
 */
abstract class AbstractPaginator extends PrimeSerializable
{
    const DEFAULT_PAGE  = 1;
    const DEFAULT_LIMIT = 20;
    
    /**
     * Current query
     * 
     * @var QueryInterface
     */
    protected $query;
    
    /**
     * Current collection
     * 
     * @var array|CollectionInterface
     */
    protected $collection = [];
    
    /**
     * Total size of the collection
     * 
     * @var int 
     */
    protected $size;
    
    /**
     * Current page
     * 
     * @var int
     */
    protected $page;
    
    /**
     * Number of entities loaded in the collection
     * 
     * @var int
     */
    protected $maxRows;
    
    
    /**
     * {@inheritdoc}
     */
    public function collection()
    {
        return $this->collection;
    }
    
    /**
     * Get the query
     * 
     * @return QueryInterface
     */
    public function query()
    {
        return $this->query;
    }
    
    /**
     * Load entities
     *
     * @throws PrimeException
     */
    protected function loadCollection()
    {
        if ($this->maxRows > -1) {
            $this->query->limitPage($this->page, $this->maxRows);
        }
        
        $this->collection = $this->query->all();
    }
    
    /**
     * {@inheritdoc}
     */
    public function order($attribute = null)
    {
        $orders = $this->query->getOrders();
        
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
        return $this->query->getLimit();
    }

    /**
     * {@inheritdoc}
     */
    public function offset()
    {
        return $this->query->getOffset();
    }

    /**
     * {@inheritdoc}
     */
    public function page()
    {
        return $this->query->getPage();
    }

    /**
     * {@inheritdoc}
     */
    public function pageMaxRows()
    {
        return $this->query->getLimit();
    }
    
    /**
     * {@inheritdoc}
     */
    public function size()
    {
        if ($this->size === null) {
            $this->buildSize();
        }
        
        return $this->size;
    }
    
    /**
     * Find size of the collection
     */
    protected function buildSize()
    {
        $currentSize = $this->count();
            
        if (!$this->query->isLimitQuery()) {
            $this->size = $currentSize;
        } elseif ($currentSize + $this->query->getOffset() < $this->query->getLimit()) {
            $this->size = $currentSize + $this->query->getOffset();
        } else {
            $this->size = $this->query->paginationCount();
        }
    }
    
    /**
     * SPL - Countable
     *
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->collection);
    }
    
    //--------- collection interface
    
    /**
     * {@inheritdoc}
     */
    public function pushAll(array $items)
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        $this->collection->pushAll($items);
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function push($item)
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        $this->collection->push($item);
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function put($key, $item)
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        $this->collection->put($key, $item);
        
        return $this;
    }
    
    /**
     * SPL - ArrayAccess
     *
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        $this->collection[$key] = $value;
    }
    
    /**
     * {@inheritdoc}
     */
    public function all()
    {
        if (!($this->collection instanceof CollectionInterface)) {
            return $this->collection;
        }
        
        return $this->collection->all();
    }
    
    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        return $this->collection->get($key, $default);
    }
    
    /**
     * SPL - ArrayAccess
     *
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        return $this->collection[$key];
    }
    
    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        return $this->collection->has($key);
    }
    
    /**
     * SPL - ArrayAccess
     *
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        return isset($this->collection[$key]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        $this->collection->remove($key);
        
        return $this;
    }
    
    /**
     * SPL - ArrayAccess
     *
     * {@inheritdoc}
     */
    public function offsetUnset($key)
    {
        unset($this->collection[$key]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        $this->collection->clear();
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        return $this->collection->keys();
    }
    
    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        return $this->collection->isEmpty();
    }
    
    /**
     * {@inheritdoc}
     */
    public function map($callback)
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        $this->collection = $this->collection->map($callback);
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function filter($callback = null)
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        $this->collection = $this->collection->filter($callback);
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function groupBy($groupBy, $mode = PaginatorInterface::GROUPBY)
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        $this->collection = $this->collection->groupBy($groupBy, $mode);
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function contains($element)
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        return $this->collection->contains($element);
    }
    
    /**
     * {@inheritdoc}
     */
    public function indexOf($value, $strict = false)
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        return $this->collection->indexOf($value, $strict);
    }
    
    /**
     * {@inheritdoc}
     */
    public function merge($items)
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        $this->collection = $this->collection->merge($items);
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function sort(callable $callback = null)
    {
        if (!($this->collection instanceof CollectionInterface)) {
            throw new \LogicException('Collection is not an instance of CollectionInterface. Could not call method ' . __METHOD__);
        }
        
        $this->collection = $this->collection->sort($callback);
        
        return $this;
    }
}