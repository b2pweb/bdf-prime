<?php

namespace Bdf\Prime\Entity;

/**
 * Base type for entities which need a post-construct initializer, like for instantiate embedded entities
 */
interface InitializableInterface
{
    /**
     * Call before constructor
     * 
     * Useful if your entity needs some constructor initialization that orm does not call
     */
    public function initialize(): void;
}
