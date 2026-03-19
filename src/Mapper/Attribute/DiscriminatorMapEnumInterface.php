<?php

namespace Bdf\Prime\Mapper\Attribute;

/**
 * Interface for enum classes used as discriminator map values
 *
 * @see DiscriminatorMap to use this interface
 */
interface DiscriminatorMapEnumInterface extends \UnitEnum
{
    /**
     * Get the mapper class name for this discriminator value
     *
     * @return class-string<\Bdf\Prime\Mapper\Mapper>
     */
    public function mapperClass(): string;
}
