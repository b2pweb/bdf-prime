<?php

namespace Bdf\Prime\Entity\Hydrator;

/**
 * Interface for generate hydrators
 */
interface HydratorGeneratedInterface extends HydratorInterface, MapperHydratorInterface
{
    /**
     * Get the supported entity class name
     *
     * @return string
     */
    public static function supportedPrimeClassName();

    /**
     * Get the embedded classes list, in same order as the constructor
     *
     * @return string[]
     */
    public static function embeddedPrimeClasses();
}