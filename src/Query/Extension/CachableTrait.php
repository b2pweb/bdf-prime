<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Cache\CacheKey;
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
     * @var CacheKey
     */
    protected $cacheKey = null;


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
     * Define the cache lifetime
     *
     * @param int $lifetime The cache lifetime in seconds
     *
     * @return $this
     */
    public function setCacheLifetime(int $lifetime)
    {
        $this->getCacheKey()->setLifetime($lifetime);

        return $this;
    }

    /**
     * Define the cache key
     *
     * @param string $cacheKey
     *
     * @return $this
     */
    public function setCacheKey(string $cacheKey)
    {
        $this->getCacheKey()->setKey($cacheKey);

        return $this;
    }

    /**
     * Define the cache namespace
     *
     * @param string $namespace
     *
     * @return $this
     */
    public function setCacheNamespace(string $namespace)
    {
        $this->getCacheKey()->setNamespace($namespace);

        return $this;
    }

    /**
     * Get the cache key
     *
     * @return CacheKey
     */
    public function getCacheKey(): CacheKey
    {
        if ($this->cacheKey === null) {
            return $this->cacheKey = new CacheKey(
                function () { return $this->cacheNamespace(); },
                function () { return $this->cacheKey(); }
            );
        }

        return $this->cacheKey;
    }

    /**
     * Retrieve data from cache, or execute the query and save into cache
     *
     * @return mixed
     */
    protected function executeCached()
    {
        $key = $this->getCacheKey();

        if ($this->disableCache || !$key->valid()) {
            return $this->connection->execute($this)->all();
        }

        $data = $this->cache->get($key);

        if ($data !== null) {
            return $data;
        }

        $data = $this->connection->execute($this)->all();

        $this->cache->set($key, $data);

        return $data;
    }

    /**
     * Clear the cache when a write operation is performed
     */
    protected function clearCacheOnWrite()
    {
        if ($this->cache) {
            $this->cache->flush($this->getCacheKey()->namespace());
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
