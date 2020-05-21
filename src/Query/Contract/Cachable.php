<?php

namespace Bdf\Prime\Query\Contract;

use Bdf\Prime\Cache\CacheInterface;

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
     * Disable cache one this query
     *
     * @return $this
     */
    public function disableCache();
}
