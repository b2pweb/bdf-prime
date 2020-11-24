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
     * @var CacheKey
     */
    protected $cacheKey = null;


    /**
     * @see Cachable::cache()
     */
    public function cache()
    {
        return $this->cache;
    }

    /**
     * @see Cachable::useCache()
     */
    public function useCache(int $lifetime = 0, ?string $key = null)
    {
        if ($this->cacheKey === null) {
            $this->cacheKey = new CacheKey(
                function () { return $this->cacheNamespace(); },
                $key ?? function () { return $this->cacheKey(); },
                $lifetime
            );

            return $this;
        }

        $this->cacheKey->setLifetime($lifetime);

        if ($key !== null) {
            $this->cacheKey->setKey($key);
        }

        return $this;
    }

    /**
     * @see Cachable::setCache()
     */
    public function setCache(CacheInterface $cache = null)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @see Cachable::setCacheLifetime()
     */
    public function setCacheLifetime(int $lifetime)
    {
        if (!$this->cacheKey) {
            $this->useCache();
        }

        $this->cacheKey->setLifetime($lifetime);

        return $this;
    }

    /**
     * @see Cachable::setCacheKey()
     */
    public function setCacheKey(?string $cacheKey)
    {
        if (!$this->cacheKey) {
            $this->useCache();
        }

        $this->cacheKey->setKey($cacheKey);

        return $this;
    }

    /**
     * @see Cachable::setCacheNamespace()
     */
    public function setCacheNamespace(string $namespace)
    {
        if (!$this->cacheKey) {
            $this->useCache();
        }

        $this->cacheKey->setNamespace($namespace);

        return $this;
    }

    /**
     * @see Cachable::getCacheKey()
     */
    public function getCacheKey(): ?CacheKey
    {
        return $this->cacheKey;
    }

    /**
     * Retrieve data from cache, or execute the query and save into cache
     *
     * @return mixed
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    protected function executeCached()
    {
        $key = $this->cacheKey;

        if (!$this->cache || !$key || !$key->valid()) {
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
            $this->cache->flush($this->cacheKey ? $this->cacheKey->namespace() : $this->cacheNamespace());
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
