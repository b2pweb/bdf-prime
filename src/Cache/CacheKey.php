<?php

namespace Bdf\Prime\Cache;

/**
 * Class CacheKey
 */
final class CacheKey
{
    /**
     * @var string|callable|null
     */
    private mixed $namespace;

    /**
     * @var string|callable|null
     */
    private mixed $key;

    private int $lifetime = 0;

    /**
     * CacheKey constructor.
     * @param callable|string|null $namespace
     * @param callable|string|null $key
     * @param int $lifetime
     */
    public function __construct(callable|string|null $namespace = null, callable|string|null $key = null, int $lifetime = 0)
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
    public function setNamespace(string|callable $namespace): CacheKey
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    public function key(): ?string
    {
        return $this->key === null || is_string($this->key) ? $this->key : ($this->key)();
    }

    /**
     * @param string|callable|null $key
     *
     * @return $this
     */
    public function setKey(string|callable|null $key): CacheKey
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

    /**
     * Check if the key is valid (i.e. not empty)
     *
     * @return bool
     */
    public function valid(): bool
    {
        return !empty($this->key());
    }
}
