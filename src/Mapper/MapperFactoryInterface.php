<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Mapper\NameResolver\ResolverInterface;
use Bdf\Prime\ServiceLocator;
use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;

/**
 * Resolve and create mapper instances
 */
interface MapperFactoryInterface
{
    /**
     * Create the mapper for the given entity class
     * The mapper class will be resolved by the mapper name resolver
     *
     * @param ServiceLocator $serviceLocator
     * @param class-string<E> $entityClass
     *
     * @return Mapper<E>|null The mapper instance, or null if the mapper cannot be resolved.
     * @template E as object
     */
    public function build(ServiceLocator $serviceLocator, string $entityClass): ?Mapper;

    /**
     * Create the mapper instance
     *
     * @param ServiceLocator $serviceLocator
     * @param class-string<Mapper> $mapperClass Mapper class name to create
     * @param class-string<E>|null $entityClass The entity class name. If null, it will be resolved from the mapper class name
     *
     * @return Mapper<E>|null The mapper instance, or null if the mapper class is invalid.
     * @template E as object
     */
    public function createMapper(ServiceLocator $serviceLocator, string $mapperClass, ?string $entityClass = null): ?Mapper;

    /**
     * Check if class is a mapper
     *
     * @param string $className
     *
     * @return bool
     * @psalm-assert-if-true class-string<Mapper> $className
     */
    public function isMapper(string $className): bool;

    /**
     * Check if class has a mapper
     *
     * @param string $className
     *
     * @return bool
     */
    public function isEntity(string $className): bool;

    /**
     * Get the mapper name resolver
     *
     * @return ResolverInterface
     */
    public function getNameResolver(): ResolverInterface;

    /**
     * Set meta cache
     *
     * @param Psr16CacheInterface|null $cache
     *
     * @return void
     */
    public function setMetadataCache(?Psr16CacheInterface $cache): void;

    /**
     * Get meta cache
     *
     * @return Psr16CacheInterface|null
     */
    public function getMetadataCache(): ?Psr16CacheInterface;

    /**
     * Set the result cache
     *
     * @param null|CacheInterface $cache
     *
     * @return void
     */
    public function setResultCache(?CacheInterface $cache): void;

    /**
     * Get the resul cache
     *
     * @return CacheInterface
     */
    public function getResultCache(): ?CacheInterface;
}
