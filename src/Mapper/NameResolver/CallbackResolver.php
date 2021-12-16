<?php

namespace Bdf\Prime\Mapper\NameResolver;

use Bdf\Prime\Mapper\Mapper;
use Closure;

/**
 * CallbackResolver
 *
 * @deprecated Prefer use anonymous class implementing ResolverInterface
 */
class CallbackResolver implements ResolverInterface
{
    /**
     * @var Closure(class-string):class-string<Mapper>
     */
    protected $resolver;
    
    /**
     * @var Closure(class-string<Mapper>):class-string
     */
    protected $reverser;
    
    /**
     * @param Closure(class-string):class-string<Mapper> $resolver
     * @param Closure(class-string<Mapper>):class-string $reverser
     */
    public function __construct(Closure $resolver, Closure $reverser)
    {
        $this->resolver = $resolver;
        $this->reverser = $reverser;
    }
    
    /**
     * {@inheritdoc}
     */
    public function resolve(string $entityClass): string
    {
        return ($this->resolver)($entityClass);
    }
    
    /**
     * {@inheritdoc}
     */
    public function reverse(string $mapperClass): string
    {
        return ($this->reverser)($mapperClass);
    }
}
