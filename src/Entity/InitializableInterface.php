<?php

namespace Bdf\Prime\Entity;

/**
 * Interface InitializableInterface
 * 
 * @package Bdf\Prime\Entity
 */
interface InitializableInterface
{
    /**
     * Call before constructor
     * 
     * Usefull if your entity needs some constructor initialization that orm does not call
     */
    public function initialize();
}