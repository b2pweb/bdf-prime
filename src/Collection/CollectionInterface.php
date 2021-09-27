<?php

namespace Bdf\Prime\Collection;

use Countable;
use ArrayAccess;
use Traversable;

/**
 * CollectionInterface
 * 
 * @template E
 *
 * @implements ArrayAccess<array-key, E>
 * @implements Traversable<array-key, E>
 */
interface CollectionInterface extends Countable, ArrayAccess, Traversable
{
    const GROUPBY = 0;
    const GROUPBY_COMBINE = 1;
    const GROUPBY_PRESERVE = 2;
    const GROUPBY_CUSTOM = 3;
    
    /**
     * Replace all items
     * 
     * @param E[] $items
     * 
     * @return $this
     */
    public function pushAll(array $items);
    
    /**
     * Push an item onto the end of the collection.
     *
     * @param E $item
     * 
     * @return $this
     */
    public function push($item);
    
    /**
     * Put an item in the collection by key.
     *
     * @param array-key|null $key
     * @param E $item
     * 
     * @return $this
     */
    public function put($key, $item);
    
    /**
     * Get all of the items in the collection.
     *
     * @return E[]
     */
    public function all();
    
    /**
     * Get an item from the collection by key.
     *
     * @param array-key $key
     * @param E|null $default
     * 
     * @return E|null
     */
    public function get($key, $default = null);
    
    /**
     * Determine if an item exists in the collection by key.
     *
     * @param array-key $key
     * @return bool
     */
    public function has($key);
    
    /**
     * Remove an item from the collection by key.
     *
     * @param array-key $key
     * 
     * @return $this
     */
    public function remove($key);
    
    /**
     * Clear the collection
     * 
     * @return $this
     */
    public function clear();
    
    /**
     * Get the keys of the collection items.
     *
     * @return list<array-key>
     */
    public function keys();
    
    /**
     * Determine if collection is empty
     * 
     * @return boolean
     */
    public function isEmpty();
    
    /**
     * Runs a callback to every items
     *
     * @param callable(E):R $callback The function to run
     * @return self<R> The new collection
     *
     * @template R
     */
    public function map($callback);
    
    /**
     * Filter every entites with a callback
     *
     * @param callable(E):bool $callback The function to run
     * @return static The new filtered collection
     */
    public function filter($callback = null);
    
    /**
     * Group an associative array by a field or using a callback.
     *
     * The mode values:
     * 0. rebuild on group key,
     * 1. combine if group key exist,
     * 2. combine and preservee key
     * 3. custom injection in the new collection. The callback has to be a callable
     * 
     * @param callable(mixed,array-key,array)|string $groupBy
     * @param int $mode
     * 
     * @return self
     *
     * @throws \LogicException if the mode custom is set and the callback is not a callable
     */
    public function groupBy($groupBy, $mode = self::GROUPBY);
    
    /**
     * Determine if an item exists in the collection.
     *
     * @param mixed $element
     * 
     * @return bool
     */
    public function contains($element);
    
    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * @param mixed $value
     * @param bool $strict
     * 
     * @return array-key|false
     */
    public function indexOf($value, $strict = false);
    
    /**
     * Merge the collection with the given items.
     *
     * @param E[]|CollectionInterface<E> $items
     * @return self<E>
     */
    public function merge($items);
    
    /**
     * Sort through each item with a callback.
     *
     * @param  callable|null  $callback
     * @return self<E>
     */
    public function sort(callable $callback = null);

    /**
     * Export entity's properties in array
     *
     * @return E[]
     */
    public function toArray();
}