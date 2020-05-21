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
    public function get($namespace, $key)
    {
        if (!isset($this->data[$namespace][$key])) {
            return null;
        }

        return $this->data[$namespace][$key];
    }

    /**
     * {@inheritDoc}
     */
    public function set($namespace, $key, $data)
    {
        $this->data[$namespace][$key] = $data;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($namespace, $key)
    {
        unset($this->data[$namespace][$key]);
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
