<?php

namespace Bdf\Prime\Mapper;

/**
 * Interface for provide Mapper factory to created mapper instances
 *
 * @see MapperFactory::createMapper()
 */
interface MapperFactoryAwareInterface
{
    /**
     * @param MapperFactory $mapperFactory
     * @return void
     * @internal
     */
    public function setMapperFactory(MapperFactory $mapperFactory): void;
}
