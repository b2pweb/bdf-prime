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
     * @psalm-suppress MismatchingDocblockParamType
     * Parameter type will be changed to MapperFactoryInterface in 3.0
     * @param MapperFactoryInterface $mapperFactory
     * @return void
     * @internal
     */
    public function setMapperFactory(MapperFactory $mapperFactory): void;
}
