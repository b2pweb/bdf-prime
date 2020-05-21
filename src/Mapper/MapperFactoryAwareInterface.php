<?php

namespace Bdf\Prime\Mapper;

/**
 * MapperFactoryAwareInterface
 * 
 * @package Bdf\Prime\Mapper
 */
interface MapperFactoryAwareInterface
{
    /**
     * @param MapperFactory $mapperFactory
     */
    public function setMapperFactory(MapperFactory $mapperFactory);
}
