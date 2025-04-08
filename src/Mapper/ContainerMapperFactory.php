<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Mapper\NameResolver\ResolverInterface;
use Bdf\Prime\ServiceLocator;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;

/**
 * Mapper factory using a PSR-11 container to instantiate mappers
 * This factory allows to inject services into mappers
 *
 * Note: This class will extend AbstractMapperFactory in version 3.0
 */
final class ContainerMapperFactory extends MapperFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container, ?ResolverInterface $nameResolver = null, ?Psr16CacheInterface $metadataCache = null, ?CacheInterface $resultCache = null, ?ClockInterface $clock = null)
    {
        parent::__construct($nameResolver, $metadataCache, $resultCache, $clock);

        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function instantiateMapper(ServiceLocator $locator, string $mapperClass): Mapper
    {
        return $this->container->get($mapperClass);
    }

    /**
     * {@inheritdoc}
     */
    public function isMapper(string $className): bool
    {
        return $this->container->has($className) && parent::isMapper($className);
    }
}
