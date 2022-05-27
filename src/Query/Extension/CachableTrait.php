<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Cache\CacheKey;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ArrayResultSet;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Query\Contract\Cachable;
use Bdf\Util\Arr;

/**
 * Provides result cache on queries
 *
 * @see Cachable
 *
 * @psalm-require-implements Cachable
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
     * {@inheritdoc}
     *
     * @see Cachable::cache()
     */
    public function cache(): ?CacheInterface
    {
        return $this->cache;
    }

    /**
     * {@inheritdoc}
     *
     * @see Cachable::useCache()
     */
    public function useCache(int $lifetime = 0, ?string $key = null)
    {
        if ($this->cacheKey === null) {
            $this->cacheKey = new CacheKey(
                function () {
                    return $this->cacheNamespace();
                },
                $key ?? function () {
                    return $this->cacheKey();
                },
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
     * {@inheritdoc}
     *
     * @see Cachable::setCache()
     */
    public function setCache(CacheInterface $cache = null)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
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
     * {@inheritdoc}
     *
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
     * {@inheritdoc}
     *
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
     * {@inheritdoc}
     *
     * @see Cachable::getCacheKey()
     */
    public function getCacheKey(): ?CacheKey
    {
        return $this->cacheKey;
    }

    /**
     * Retrieve data from cache, or execute the query and save into cache
     *
     * @return ResultSetInterface<array<string, mixed>>
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    protected function executeCached(): ResultSetInterface
    {
        $key = $this->cacheKey;

        if (!$this->cache || !$key || !$key->valid()) {
            /** @psalm-suppress InvalidArgument */
            return $this->connection->execute($this);
        }

        $data = $this->cache->get($key);

        if ($data !== null) {
            return new ArrayResultSet($data);
        }

        /** @psalm-suppress InvalidArgument */
        $result = $this->connection->execute($this);

        $data = $result->all();
        $this->cache->set($key, $data);

        return new ArrayResultSet($data);
    }

    /**
     * Clear the cache when a write operation is performed
     *
     * @return void
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
    protected function cacheKey(): ?string
    {
        return null;
    }

    /**
     * Get cache namespace
     * The namespace is in form : [connection name] ":" [table name]
     *
     * @return string
     */
    protected function cacheNamespace(): string
    {
        $ns = $this->connection->getName().':';

        foreach ($this->statements['tables'] as $from) {
            return $ns.$from['table'];
        }

        return $ns;
    }
}
