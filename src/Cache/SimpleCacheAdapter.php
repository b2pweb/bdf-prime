<?php

namespace Bdf\Prime\Cache;

use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;

use function rawurlencode;

/**
 * Adapt PSR-16 simple cache to Prime cache interface
 *
 * Keys are formatted as follow : <namespace>[<version>][<key>], encoded with rawurlencode,
 * which generate a key like "namespace%5B1%5D%5Bid%5D"
 *
 * Url encoding is used to avoid conflict with reserved characters.
 *
 * The version is used to invalidate single namespace, when calling flush(). So flush will not actually remove the data.
 */
final class SimpleCacheAdapter implements CacheInterface
{
    private Psr16CacheInterface $cache;

    public function __construct(Psr16CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function get(CacheKey $key)
    {
        return $this->cache->get($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function set(CacheKey $key, $data): void
    {
        $ttl = $key->lifetime();

        if ($ttl <= 0) {
            $ttl = null;
        }

        $this->cache->set($this->getKey($key), $data, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(CacheKey $key): void
    {
        $this->cache->delete($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function flush(string $namespace): void
    {
        $version = $this->getVersion($namespace);
        $this->cache->set(rawurlencode($namespace.'[version]'), $version + 1);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->cache->clear();
    }

    private function getKey(CacheKey $key): string
    {
        $namespace = $key->namespace();
        $version = $this->getVersion($namespace);

        return rawurlencode($namespace.'['.$version.']['.$key->key().']');
    }

    private function getVersion(string $namespace): int
    {
        return (int) $this->cache->get(rawurlencode($namespace.'[version]')) ?: 1;
    }
}
