<?php

namespace Bdf\Prime\Cache;

use Doctrine\Common\Cache\CacheProvider;

/**
 * Adapter for doctrine cache provider
 *
 * @deprecated Since 2.2. Use SimpleCacheAdapter or CachePoolAdapter instead.
 */
class DoctrineCacheAdapter implements CacheInterface
{
    /**
     * @var CacheProvider
     */
    private $driver;

    /**
     * @var CacheProvider[]
     */
    private $driverByNamespace = [];


    /**
     * DoctrineCacheAdapter constructor.
     *
     * @param CacheProvider $driver
     */
    public function __construct(CacheProvider $driver)
    {
        $this->driver = $driver;
    }

    /**
     * {@inheritdoc}
     */
    public function get(CacheKey $key)
    {
        $data = $this->namespace($key->namespace())->fetch($key->key());

        return $data === false ? null : $data;
    }

    /**
     * {@inheritdoc}
     */
    public function set(CacheKey $key, $data): void
    {
        $this->namespace($key->namespace())->save($key->key(), $data, $key->lifetime());
    }

    /**
     * {@inheritdoc}
     */
    public function delete(CacheKey $key): void
    {
        $this->namespace($key->namespace())->delete($key->key());
    }

    /**
     * {@inheritdoc}
     */
    public function flush(string $namespace): void
    {
        $this->namespace($namespace)->deleteAll();
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->driver->flushAll();

        foreach ($this->driverByNamespace as $driver) {
            $driver->flushAll();
        }
    }

    /**
     * Get the cache provider for the given namespace
     *
     * @param string $ns
     *
     * @return CacheProvider
     */
    private function namespace(string $ns): CacheProvider
    {
        if (isset($this->driverByNamespace[$ns])) {
            return $this->driverByNamespace[$ns];
        }

        $driver = clone $this->driver;
        $driver->setNamespace($ns);

        return $this->driverByNamespace[$ns] = $driver;
    }
}
