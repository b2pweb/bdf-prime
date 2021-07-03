<?php

namespace Bdf\Prime\Query\Pagination\WalkStrategy;

/**
 * Handle key for KeyWalkStrategy
 *
 * @template E as object
 */
interface KeyInterface
{
    /**
     * Get the key name (should be the PHP entity field name)
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get the key value from an entity
     *
     * @param E $entity The entity to extract
     *
     * @return mixed The attribute value
     */
    public function get($entity);
}
