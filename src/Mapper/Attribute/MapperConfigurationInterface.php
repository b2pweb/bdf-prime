<?php

namespace Bdf\Prime\Mapper\Attribute;

use Bdf\Prime\Mapper\Mapper;

/**
 * Base type for attributes used for configure the mapper
 */
interface MapperConfigurationInterface
{
    /**
     * Apply configuration on the mapper
     *
     * @param Mapper $mapper
     * @return void
     */
    public function configure(Mapper $mapper): void;
}
