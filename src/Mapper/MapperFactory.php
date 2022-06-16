<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Entity\Hydrator\MapperHydrator;
use Bdf\Prime\Entity\Hydrator\MapperHydratorInterface;
use Bdf\Prime\Mapper\NameResolver\ResolverInterface;
use Bdf\Prime\Mapper\NameResolver\SuffixResolver;
use Bdf\Prime\ServiceLocator;
use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;

/**
 * @package Bdf\Prime\Mapper
 */
class MapperFactory
{
    /**
     * @var ResolverInterface
     */
    private $nameResolver;

    /**
     * @var Psr16CacheInterface
     */
    private $metadataCache;

    /**
     * @var CacheInterface
     */
    private $resultCache;

    /**
     * @param ResolverInterface $nameResolver
     * @param Psr16CacheInterface|null $metadataCache
     * @param CacheInterface|null $resultCache
     */
    public function __construct(ResolverInterface $nameResolver = null, Psr16CacheInterface $metadataCache = null, CacheInterface $resultCache = null)
    {
        $this->nameResolver = $nameResolver ?: new SuffixResolver();
        $this->metadataCache = $metadataCache;
        $this->resultCache = $resultCache;
    }

    /**
     * Get associated entity mapper
     *
     * @param ServiceLocator $serviceLocator
     * @param class-string<E> $entityClass
     *
     * @return Mapper<E>|null
     * @template E as object
     */
    public function build(ServiceLocator $serviceLocator, $entityClass): ?Mapper
    {
        return $this->createMapper($serviceLocator, $this->nameResolver->resolve($entityClass), $entityClass);
    }

    /**
     * Get mapper object
     *
     * @param ServiceLocator $serviceLocator
     * @param class-string<Mapper> $mapperClass
     * @param class-string<E>|null $entityClass
     *
     * @return Mapper<E>|null
     * @template E as object
     */
    public function createMapper(ServiceLocator $serviceLocator, $mapperClass, $entityClass = null): ?Mapper
    {
        if (!$this->isMapper($mapperClass)) {
            return null;
        }

        if ($entityClass === null) {
            $entityClass = $this->nameResolver->reverse($mapperClass);
        }

        $metadata = null;
        if ($this->metadataCache !== null) {
            $cacheKey = $this->getCacheKey($mapperClass);
            $metadata = $this->metadataCache->get($cacheKey);
        }

        $hydrator = $serviceLocator->hydrator($entityClass);

        if (!($hydrator instanceof MapperHydratorInterface)) {
            $hydrator = new MapperHydrator();
        }

        /** @var Mapper $mapper */
        $mapper = new $mapperClass($serviceLocator, $entityClass, $metadata, $hydrator, $this->resultCache);

        if ($this->metadataCache !== null && $metadata === null) {
            $this->metadataCache->set($cacheKey, $mapper->metadata());
        }

        if ($mapper instanceof MapperFactoryAwareInterface) {
            $mapper->setMapperFactory($this);
        }

        return $mapper;
    }

    /**
     * Check if class is a mapper
     *
     * @param string $className
     *
     * @return bool
     * @psalm-assert-if-true class-string<Mapper> $className
     */
    public function isMapper($className)
    {
        return is_subclass_of($className, Mapper::class);
    }

    /**
     * Check if class has a mapper
     *
     * @param string $className
     *
     * @return bool
     */
    public function isEntity($className)
    {
        return $this->isMapper($this->nameResolver->resolve($className));
    }

    /**
     * Create the valid cache key from mapperClass
     *
     * @param string $mapperClass
     *
     * @return string
     */
    private function getCacheKey($mapperClass)
    {
        return str_replace('\\', '.', $mapperClass);
    }

    /**
     * Get the mapper name resolver
     *
     * @return ResolverInterface
     */
    public function getNameResolver()
    {
        return $this->nameResolver;
    }

    /**
     * Set meta cache
     *
     * @param null|Psr16CacheInterface $cache
     *
     * @return void
     */
    public function setMetadataCache(?Psr16CacheInterface $cache): void
    {
        $this->metadataCache = $cache;
    }

    /**
     * Get meta cache
     *
     * @return Psr16CacheInterface
     */
    public function getMetadataCache(): ?Psr16CacheInterface
    {
        return $this->metadataCache;
    }

    /**
     * Set the result cache
     *
     * @param null|CacheInterface $cache
     *
     * @return void
     */
    public function setResultCache(?CacheInterface $cache): void
    {
        $this->resultCache = $cache;
    }

    /**
     * Get the resul cache
     *
     * @return CacheInterface
     */
    public function getResultCache(): ?CacheInterface
    {
        return $this->resultCache;
    }
}
