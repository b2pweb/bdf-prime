<?php

namespace Bdf\Prime\Entity\Hydrator;

/**
 * Interface for generated hydrators
 *
 * @template E as object
 * @extends MapperHydratorInterface<E>
 */
interface HydratorGeneratedInterface extends HydratorInterface, MapperHydratorInterface
{
    /**
     * Get the supported entity class name
     *
     * @return class-string
     */
    public static function supportedPrimeClassName(): string;

    /**
     * Get the embedded classes list, in same order as the constructor
     *
     * @return list<class-string>
     */
    public static function embeddedPrimeClasses(): array;
}
