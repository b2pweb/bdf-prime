<?php

namespace Bdf\Prime\Connection;

use ArrayAccess;
use IteratorAggregate;

/**
 * Config
 * 
 * Manage multidimensional array. Config allows developer to easy get a value or a node
 * 
 * <code>
 * $config = new Config([
 *    'connection_name' => ['item1' => 'value1']
 * ]);
 * $config->get('connection_name'); //['item1' => 'value1']
 * </code>
 */
class ConnectionConfig implements IteratorAggregate, ArrayAccess
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Set the config content and the read only mode
     * 
     * @param array $config    Data
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        return $this->raw($key, $default);
    }
    
    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return isset($this->config[$key]);
    }
    
    /**
     * Set datum
     * 
     * @param string $key
     * @param mixed  $value
     * 
     * @return $this
     */
    public function set($key, $value)
    {
        if ($value instanceof self) {
            $value = $value->config;
        }
        
        if ($key === null) {
            $this->config[] = $value;
        } else {
            $this->config[$key] = $value;
        }

        return $this;
    }
    
    /**
     * Remove a datum
     * 
     * @param string $key
     * 
     * @return $this
     */
    public function remove($key)
    {
        unset($this->config[$key]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->config;
    }

    /**
     * SPL - IteratorAggregate
     *
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->config as $key => $value) {
            yield $key => $this->get($key);
        }
    }

    /**
     * SPL - ArrayAccess
     * 
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * SPL - ArrayAccess
     * 
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * SPL - ArrayAccess
     * 
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * SPL - ArrayAccess
     * 
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Get the raw value
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function raw($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
