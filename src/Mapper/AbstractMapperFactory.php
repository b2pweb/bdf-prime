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
use function strtr;

/**
 * Base implementation of MapperFactoryInterface
 * Handle mapper resolution and configuration. Mapper instantiation is performed by the abstract method instantiateMapper()
 */
abstract class AbstractMapperFactory implements MapperFactoryInterface
{
    private ResolverInterface $nameResolver;
    private ?Psr16CacheInterface $metadataCache;
    private ?CacheInterface $resultCache;

    /**
     * @param ResolverInterface|null $nameResolver
     * @param Psr16CacheInterface|null $metadataCache
     * @param CacheInterface|null $resultCache
     */
    public function __construct(?ResolverInterface $nameResolver = null, ?Psr16CacheInterface $metadataCache = null, ?CacheInterface $resultCache = null)
    {
        $this->nameResolver = $nameResolver ?? new SuffixResolver();
        $this->metadataCache = $metadataCache;
        $this->resultCache = $resultCache;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ServiceLocator $serviceLocator, string $entityClass): ?Mapper
    {
        return $this->createMapper($serviceLocator, $this->nameResolver->resolve($entityClass), $entityClass);
    }

    /**
     * {@inheritdoc}
     *
     * @param ServiceLocator $serviceLocator
     * @param class-string<Mapper> $mapperClass
     * @param class-string<E>|null $entityClass
     *
     * @return Mapper<E>|null
     * @template E as object
     */
    public function createMapper(ServiceLocator $serviceLocator, string $mapperClass, ?string $entityClass = null): ?Mapper
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

        /** @var Mapper<E> $mapper */
        $mapper = $this->instantiateMapper($serviceLocator, $mapperClass);

        $mapper->setEntityClass($entityClass);
        $mapper->setHydrator($hydrator);

        if ($metadata) {
            $mapper->setMetadata($metadata);
        }

        if ($this->resultCache) {
            $mapper->setResultCache($this->resultCache);
        }

        $mapper->build();

        if ($this->metadataCache !== null && $metadata === null) {
            $this->metadataCache->set($cacheKey, $mapper->metadata());
        }

        if ($mapper instanceof MapperFactoryAwareInterface) {
            $mapper->setMapperFactory($this);
        }

        return $mapper;
    }

    /**
     * {@inheritdoc}
     */
    public function isMapper(string $className): bool
    {
        return is_subclass_of($className, Mapper::class);
    }

    /**
     * {@inheritdoc}
     */
    public function isEntity(string $className): bool
    {
        return $this->isMapper($this->nameResolver->resolve($className));
    }

    /**
     * {@inheritdoc}
     */
    final public function setMetadataCache(?Psr16CacheInterface $cache): void
    {
        $this->metadataCache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    final public function getMetadataCache(): ?Psr16CacheInterface
    {
        return $this->metadataCache;
    }

    /**
     * {@inheritdoc}
     */
    final public function setResultCache(?CacheInterface $cache): void
    {
        $this->resultCache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    final public function getResultCache(): ?CacheInterface
    {
        return $this->resultCache;
    }

    /**
     * {@inheritdoc}
     */
    final public function getNameResolver(): ResolverInterface
    {
        return $this->nameResolver;
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
        return strtr($mapperClass, '\\', '.');
    }

    /**
     * Instantiate the given mapper class
     *
     * @param ServiceLocator $locator
     * @param class-string<Mapper> $mapperClass Mapper class name
     *
     * @return Mapper
     */
    abstract protected function instantiateMapper(ServiceLocator $locator, string $mapperClass): Mapper;
}
