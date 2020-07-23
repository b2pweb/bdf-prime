<?php

namespace Bdf\Prime\Cache;

/**
 * CacheInterface
 */
interface CacheInterface
{
    /**
     * Read key from namespace
     * 
     * @param CacheKey $key
     * 
     * @return array
     */
    public function get(CacheKey $key);

    /**
     * Write data on namespace
     * 
     * @param CacheKey $key
     * @param array  $data
     */
    public function set(CacheKey $key, $data);

    /**
     * Delete key from namespace
     * 
     * @param CacheKey $key
     */
    public function delete(CacheKey $key);

    /**
     * Flush namespace only
     * 
     * @param string $namespace
     */
    public function flush($namespace);

    /**
     * Clear all cache
     */
    public function clear();
}
