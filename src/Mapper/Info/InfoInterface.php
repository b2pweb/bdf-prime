<?php

namespace Bdf\Prime\Mapper\Info;

/**
 * Helper type for extract information about a mapper
 */
interface InfoInterface
{
    /**
     * Get the property name
     * 
     * @return string
     */
    public function name(): string;
    
    /**
     * Check whether the property is an object
     * 
     * @return bool
     */
    public function isObject(): bool;

    /**
     * Check whether the relation is an array
     *
     * @return bool
     */
    public function isArray(): bool;

    /**
     * Is the property embed in the entity
     *
     * @return bool
     */
    public function isEmbedded(): bool;

    /**
     * Is the property belongs to the entity
     *
     * @return bool
     */
    public function belongsToRoot(): bool;
}
