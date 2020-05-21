<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\Contract\Cachable;

/**
 * Provides result cache on queries
 *
 * @see Cachable
 *
 * @property ConnectionInterface $connection
 *
 * @todo Cache statement instead of assoc array result ?
 */
trait CachableTrait
{
    /**
     * @var null|CacheInterface
     */
    protected $cache;

    /**
     * @var bool
     */
    protected $disableCache = true;


    /**
     * @see Cachable::cache()
     */
    public function cache()
    {
        return $this->disableCache ? null : $this->cache;
    }

    /**
     * @see Cachable::setCache()
     */
    public function setCache(CacheInterface $cache = null)
    {
        $this->disableCache = $cache === null;
        $this->cache = $cache;

        return $this;
    }

    /**
     * @see Cachable::disableCache()
     */
    public function disableCache()
    {
        $this->disableCache = true;

        return $this;
    }

    /**
     * Retrieve data from cache, or execute the query and save into cache
     *
     * @return mixed
     */
    protected function executeCached()
    {
        if ($this->disableCache || null === ($key = $this->cacheKey())) {
            return $this->connection->execute($this)->all();
        }

        $namespace = $this->cacheNamespace();
        $data = $this->cache->get($namespace, $key);

        if ($data !== null) {
            return $data;
        }

        $data = $this->connection->execute($this)->all();

        $this->cache->set($namespace, $key, $data);

        return $data;
    }

    /**
     * Clear the cache when a write operation is performed
     */
    protected function clearCacheOnWrite()
    {
        if ($this->cache) {
            $this->cache->flush($this->cacheNamespace());
        }
    }

    /**
     * Get the cache key
     * The cache key is generated from the query string
     *
     * @return string
     */
    protected function cacheKey()
    {
        return null;
    }

    /**
     * Get cache namespace
     * The namespace is in form : [connection name] ":" [table name]
     *
     * @return string
     */
    protected function cacheNamespace()
    {
        return $this->connection->getName().':'.(isset($this->statements['tables'][0]['table']) ? $this->statements['tables'][0]['table'] : '');
    }
}
