<?php

namespace Bdf\Prime\Cache;

/**
 * ArrayCache
 */
class ArrayCache implements CacheInterface
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * {@inheritDoc}
     */
    public function get(CacheKey $key)
    {
        if (!isset($this->data[$key->namespace()][$key->key()])) {
            return null;
        }

        return $this->data[$key->namespace()][$key->key()];
    }

    /**
     * {@inheritDoc}
     */
    public function set(CacheKey $key, $data)
    {
        $this->data[$key->namespace()][$key->key()] = $data;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(CacheKey $key)
    {
        unset($this->data[$key->namespace()][$key->key()]);
    }

    /**
     * {@inheritDoc}
     */
    public function flush($namespace)
    {
        unset($this->data[$namespace]);
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        $this->data = [];
    }
}
