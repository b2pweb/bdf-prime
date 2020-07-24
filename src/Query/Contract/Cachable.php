<?php

namespace Bdf\Prime\Query\Contract;

use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Cache\CacheKey;

/**
 * Cachable query keeps the results into a cache
 */
interface Cachable
{
    /**
     * Get the configured cache for this query
     *
     * @return CacheInterface|null
     */
    public function cache();

    /**
     * Set a cache to the query
     *
     * @param CacheInterface|null $cache The cache instance, or null to disable cache
     *
     * @return $this
     */
    public function setCache(CacheInterface $cache = null);

    /**
     * Enable cache for this query
     *
     * @param int $lifetime The cache lifetime
     * @param string|null $key The cache key. If null, a key will be generated
     *
     * @return $this
     *
     * @see Cachable::setCacheLifetime()
     * @see Cachable::setCacheKey()
     * @see Cachable::setCacheNamespace()
     */
    public function useCache(int $lifetime = 0, ?string $key = null);

    /**
     * Define the cache lifetime
     * Enable the cache if not yet enabled
     *
     * @param int $lifetime The cache lifetime in seconds
     *
     * @return $this
     */
    public function setCacheLifetime(int $lifetime);

    /**
     * Define the cache key
     * Enable the cache if not yet enabled
     *
     * @param string|null $cacheKey
     *
     * @return $this
     */
    public function setCacheKey(?string $cacheKey);

    /**
     * Define the cache namespace
     * Enable the cache if not yet enabled
     *
     * The namespace is used to flush cache on write
     *
     * @param string $namespace
     *
     * @return $this
     */
    public function setCacheNamespace(string $namespace);

    /**
     * Get the cache key
     *
     * @return CacheKey|null The cache key, or null if disabled
     */
    public function getCacheKey(): ?CacheKey;
}
