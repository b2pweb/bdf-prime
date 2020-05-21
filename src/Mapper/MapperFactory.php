<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Entity\Hydrator\MapperHydrator;
use Bdf\Prime\Entity\Hydrator\MapperHydratorInterface;
use Bdf\Prime\Mapper\NameResolver\ResolverInterface;
use Bdf\Prime\Mapper\NameResolver\SuffixResolver;
use Bdf\Prime\ServiceLocator;
use Psr\SimpleCache\CacheInterface;

/**
 * @package Bdf\Prime\Mapper
 */
class MapperFactory
{
    /**
     * @var ResolverInterface
     */
    protected $nameResolver;
    
    /**
     * @var CacheInterface
     */
    protected $cache;
    
    /**
     * @param ResolverInterface $nameResolver
     */
    public function __construct(ResolverInterface $nameResolver = null)
    {
        $this->nameResolver = $nameResolver ?: new SuffixResolver();
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
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }
    
    /**
     * Get meta cache
     * 
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }
    
    /**
     * Get associated entity mapper
     *
     * @param ServiceLocator $serviceLocator
     * @param string $entityClass
     *
     * @return Mapper
     */
    public function build(ServiceLocator $serviceLocator, $entityClass)
    {
        return $this->createMapper($serviceLocator, $this->nameResolver->resolve($entityClass), $entityClass);
    }
    
    /**
     * Get mapper object
     *
     * @param ServiceLocator $serviceLocator
     * @param string $mapperClass
     * @param string $entityClass
     *
     * @return Mapper
     */
    public function createMapper(ServiceLocator $serviceLocator, $mapperClass, $entityClass = null)
    {
        if (!$this->isMapper($mapperClass)) {
            return null;
        }
        
        if ($entityClass === null) {
            $entityClass = $this->nameResolver->reverse($mapperClass);
        }
        
        $metadata = null;
        if ($this->cache !== null) {
            $cacheKey = $this->getCacheKey($mapperClass);
            $metadata = $this->cache->get($cacheKey);
        }

        $hydrator = $serviceLocator->hydrator($entityClass);

        if (!($hydrator instanceof MapperHydratorInterface)) {
            $hydrator = new MapperHydrator();
        }

        /** @var Mapper $mapper */
        $mapper = new $mapperClass($serviceLocator, $entityClass, $metadata, $hydrator);
        
        if ($this->cache !== null && $metadata === null) {
            $this->cache->set($cacheKey, $mapper->metadata());
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
}
