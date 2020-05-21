<?php

namespace Bdf\Prime\Mapper\Info;

/**
 * InfoInterface
 * 
 * @package Bdf\Prime\Mapper\Info
 */
interface InfoInterface
{
    /**
     * Get the property name
     * 
     * @return string
     */
    public function name();
    
    /**
     * Check whether the property is an object
     * 
     * @return bool
     */
    public function isObject();

    /**
     * Check whether the relation is an array
     *
     * @return bool
     */
    public function isArray();

    /**
     * Is the property embed in the entity
     *
     * @return bool
     */
    public function isEmbedded();

    /**
     * Is the property belongs to the entity
     *
     * @return bool
     */
    public function belongsToRoot();
}
