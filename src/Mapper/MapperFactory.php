<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Entity\Hydrator\MapperHydrator;
use Bdf\Prime\Entity\Hydrator\MapperHydratorInterface;
use Bdf\Prime\Mapper\NameResolver\ResolverInterface;
use Bdf\Prime\Mapper\NameResolver\SuffixResolver;
use Bdf\Prime\ServiceLocator;
use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;

use function is_subclass_of;
use function str_replace;

/**
 * Default mapper factory
 * The mapper class will be resolved by the mapper name resolver, and instantiated by calling the constructor
 *
 * Will be marked as final in 3.0
 */
/*final*/ class MapperFactory extends AbstractMapperFactory
{
    /**
     * {@inheritdoc}
     */
    protected function instantiateMapper(ServiceLocator $locator, string $mapperClass): Mapper
    {
        return new $mapperClass($locator);
    }
}
