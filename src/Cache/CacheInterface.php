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
     * @param string $namespace
     * @param string $key
     * 
     * @return array
     */
    public function get($namespace, $key);

    /**
     * Write data on namespace
     * 
     * @param string $namespace
     * @param string $key
     * @param array  $data
     */
    public function set($namespace, $key, $data);

    /**
     * Delete key from namespace
     * 
     * @param string $namespace
     * @param string $key
     */
    public function delete($namespace, $key);

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
