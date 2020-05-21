<?php

namespace Bdf\Prime\Collection;

use ArrayIterator;
use Bdf\Prime\PrimeSerializable;
use Closure;
use IteratorAggregate;

/**
 * Basic array collection
 *
 * @author  Seb
 * @package Bdf\Prime\Collection
 */
class ArrayCollection extends PrimeSerializable implements IteratorAggregate, CollectionInterface
{
    /**
     * Container of items
     * 
     * @var array
     */
    private $items = [];
    
    
    /**
     * Create a collection
     * 
     * @param mixed $items
     */
    public function __construct($items = [])
    {
        $this->items = $this->getArrayFromItems($items);
    }
    
    /**
     * {@inheritdoc}
     */
    public function pushAll(array $items)
    {
        $this->items = $items;
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function push($item)
    {
        $this->put(null, $item);
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function put($key, $item)
    {
        if ($key === null) {
            $this->items[] = $item;
        } else {
            $this->items[$key] = $item;
        }
        
        return $this;
    }
    
    /**
     * SPL - ArrayAccess
     *
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        $this->put($key, $value);
    }
    
    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->items;
    }
    
    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->items[$key];
        }
        
        return $default;
    }
    
    /**
     * SPL - ArrayAccess
     *
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }
    
    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return isset($this->items[$key]);
    }
    
    /**
     * SPL - ArrayAccess
     *
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        return isset($this->items[$key]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->items[$key]);
        
        return $this;
    }
    
    /**
     * SPL - ArrayAccess
     *
     * {@inheritdoc}
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }
    
    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->items = [];
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return array_keys($this->items);
    }
    
    /**
     * SPL - Countable
     *
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->items);
    }
    
    /**
     * SPL - IteratorAggregate
     *
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }
    
    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return empty($this->items);
    }
    
    /**
     * {@inheritdoc}
     */
    public function map($callback)
    {
        $keys  = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        
        return new static(array_combine($keys, $items));
    }
    
    /**
     * {@inheritdoc}
     */
    public function filter($callback = null)
    {
        if ($callback !== null) {
            return new static(array_filter($this->items, $callback));
        }
        
        return new static(array_filter($this->items));
    }
    
    /**
     * {@inheritdoc}
     */
    public function groupBy($groupBy, $mode = self::GROUPBY)
    {
        $results = [];
        
        if (!is_callable($groupBy)) {
            if ($mode === self::GROUPBY_CUSTOM) {
                throw new \LogicException('Custom mode should only used with callable callback');
            }
            
            $groupBy = function($item) use($groupBy) {
                return $this->getDataFromItem($item, $groupBy);
            };
        }
        
        foreach ($this->items as $key => $item) {
            $groupKey = $groupBy($item, $key, $results);
            
            switch ($mode) {
                case self::GROUPBY:
                    $results[$groupKey] = $item;
                    break;
                
                case self::GROUPBY_COMBINE:
                    $results[$groupKey][] = $item;
                    break;
                
                case self::GROUPBY_PRESERVE:
                    $results[$groupKey][$key] = $item;
                    break;
                
                default:
                    // Custom combine, should be done in closure
                    break;
            }
        }
        
        return new static($results);
    }
    
    /**
     * {@inheritdoc}
     */
    public function contains($element)
    {
        if (!($element instanceof Closure)) {
            return in_array($element, $this->items, true);
        }
        
        foreach ($this->items as $key => $item) {
            if ($element($item, $key)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function indexOf($value, $strict = false)
    {
        if (!($value instanceof Closure)) {
            return array_search($value, $this->items, $strict);
        }
        
        foreach ($this->items as $key => $item) {
            if ($value($item, $key)) {
                return $key;
            }
        }
        
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $this->getArrayFromItems($items)));
    }
    
    /**
     * {@inheritdoc}
     */
    public function sort(callable $callback = null)
    {
        $items = $this->items;
        $callback ? uasort($items, $callback) : natcasesort($items);
        
        return new static($items);
    }
    
    /**
     * Get array of items from the given items
     * 
     * @param mixed $items
     * @return array
     */
    protected function getArrayFromItems($items)
    {
        if (is_array($items)) {
            return $items;
        }
        
        if ($items instanceof self) {
            return $items->all();
        }
        
        return (array)$items;
    }
    
    /**
     * Get value from item
     * 
     * @param array|object $item
     * @param string       $key
     * @param mixed        $default
     * 
     * @return mixed
     */
    protected function getDataFromItem($item, $key, $default = null)
    {
        if (is_array($item)) {
            return isset($item[$key]) ? $item[$key] : $default;
        }
        
        if (is_object($item)) {
            if (isset($item->$key)) {
                return $item->$key;
            }
            
            if (method_exists($item, $key)) {
                return $item->$key();
            }

            $method = 'get'.ucfirst($key);

            if (method_exists($item, $method)) {
                return $item->$method();
            }
        }
        
        return $default;
    }
}