<?php

namespace Bdf\Prime\Mapper\NameResolver;

/**
 * @package Bdf\Prime\Mapper\NameResolver
 */
interface ResolverInterface
{
    /**
     * Get mapper class name by entity class
     * 
     * @param string $entityClass
     * @return string
     */
    public function resolve($entityClass);
    
    /**
     * Get entity class name by mapper class
     * 
     * @param string $mapperClass
     * @return string
     */
    public function reverse($mapperClass);
}
