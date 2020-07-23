<?php

namespace Bdf\Prime\Cache;

/**
 * Class CacheKey
 */
final class CacheKey
{
    /**
     * @var string|callable
     */
    private $namespace;

    /**
     * @var string|callable
     */
    private $key;

    /**
     * @var integer
     */
    private $lifetime = 0;

    /**
     * CacheKey constructor.
     * @param callable|string $namespace
     * @param callable|string $key
     * @param int $lifetime
     */
    public function __construct($namespace = null, $key = null, int $lifetime = 0)
    {
        $this->namespace = $namespace;
        $this->key = $key;
        $this->lifetime = $lifetime;
    }

    /**
     * @return string
     */
    public function namespace(): string
    {
        return is_string($this->namespace) ? $this->namespace : ($this->namespace)();
    }

    /**
     * @param string|callable $namespace
     *
     * @return $this
     */
    public function setNamespace($namespace): CacheKey
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    public function key(): ?string
    {
        return is_string($this->key) ? $this->key : ($this->key)();
    }

    /**
     * @param string|callable $key
     *
     * @return $this
     */
    public function setKey($key): CacheKey
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return int
     */
    public function lifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * @param int $lifetime
     * @return $this
     */
    public function setLifetime(int $lifetime): CacheKey
    {
        $this->lifetime = $lifetime;
        return $this;
    }

    public function valid(): bool
    {
        return !empty($this->key());
    }
}
