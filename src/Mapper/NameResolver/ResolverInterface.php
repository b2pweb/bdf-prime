<?php

namespace Bdf\Prime\Mapper\NameResolver;

use Bdf\Prime\Mapper\Mapper;

/**
 * @package Bdf\Prime\Mapper\NameResolver
 */
interface ResolverInterface
{
    /**
     * Get mapper class name by entity class
     * 
     * @param class-string $entityClass
     * @return class-string<Mapper>
     */
    public function resolve(string $entityClass): string;
    
    /**
     * Get entity class name by mapper class
     * 
     * @param class-string<Mapper> $mapperClass
     * @return class-string
     */
    public function reverse(string $mapperClass): string;
}
