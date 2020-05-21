<?php

namespace Bdf\Prime\Relations\Util;

/**
 * Wrap entity keys for handle composite keys
 */
class EntityKeys
{
    /**
     * @var string[]
     */
    private $keys = [];

    /**
     * @var mixed
     */
    private $entity;

    /**
     * @var int|null
     */
    private $hash = null;


    /**
     * EntityKeys constructor.
     *
     * @param string[] $keys The entity keys values. Must be an integer index array, not an associative one
     * @param mixed $entity The attached entity
     */
    public function __construct(array $keys, $entity = null)
    {
        $this->keys = $keys;
        $this->entity = $entity;
    }

    /**
     * Attach an entity to the keys
     *
     * @param mixed $entity
     */
    public function attach($entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get the attached entity
     *
     * @return mixed
     */
    public function get()
    {
        return $this->entity;
    }

    /**
     * Compute the hash on keys for make a simple hashmap
     *
     * @return integer
     */
    public function hash()
    {
        if ($this->hash !== null) {
            return $this->hash;
        }

        $this->hash = 0;

        foreach ($this->keys as $pos => $key) {
            $this->hash ^= ($pos + 1) * crc32($key);
        }

        return $this->hash;
    }

    /**
     * Check if the two keys are same or not
     *
     * @param EntityKeys $other
     *
     * @return bool
     */
    public function equals(EntityKeys $other)
    {
        return $this->keys == $other->keys;
    }

    /**
     * Get the keys value to array
     *
     * @return string[]
     */
    public function toArray()
    {
        return $this->keys;
    }
}
