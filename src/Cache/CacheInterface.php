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
     * @return mixed|null
     */
    public function get(CacheKey $key);

    /**
     * Write data on namespace
     *
     * @param CacheKey $key
     * @param mixed $data
     *
     * @return void
     */
    public function set(CacheKey $key, $data): void;

    /**
     * Delete key from namespace
     *
     * @param CacheKey $key
     *
     * @return void
     */
    public function delete(CacheKey $key): void;

    /**
     * Flush namespace only
     *
     * @param string $namespace
     *
     * @return void
     */
    public function flush(string $namespace): void;

    /**
     * Clear all cache
     *
     * @return void
     */
    public function clear(): void;
}
