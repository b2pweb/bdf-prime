<?php

namespace Bdf\Prime\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

use function rawurlencode;

/**
 * Adapt PSR-6 cache pool to Prime cache interface
 *
 * Keys are formatted as follow : <namespace>[<version>][<key>], encoded with rawurlencode,
 * which generate a key like "namespace%5B1%5D%5Bid%5D"
 *
 * Url encoding is used to avoid conflict with reserved characters.
 *
 * The version is used to invalidate single namespace, when calling flush(). So flush will not actually remove the data.
 */
final class CachePoolAdapter implements CacheInterface
{
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function get(CacheKey $key)
    {
        $item = $this->getItem($key);

        if (!$item->isHit()) {
            return null;
        }

        return $item->get();
    }

    /**
     * {@inheritdoc}
     */
    public function set(CacheKey $key, $data): void
    {
        $item = $this->getItem($key)->set($data);

        if (($ttl = $key->lifetime()) > 0) {
            $item = $item->expiresAfter($ttl);
        }

        $this->cache->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(CacheKey $key): void
    {
        $this->cache->deleteItem($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function flush(string $namespace): void
    {
        $item = $this->cache->getItem(rawurlencode($namespace.'[version]'));
        $item = $item->set($item->isHit() ? $item->get() + 1 : 2);

        $this->cache->save($item);
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

    private function getItem(CacheKey $key): CacheItemInterface
    {
        return $this->cache->getItem($this->getKey($key));
    }

    private function getVersion(string $namespace): int
    {
        $item = $this->cache->getItem(rawurlencode($namespace.'[version]'));

        return $item->isHit() ? (int) $item->get() : 1;
    }
}
