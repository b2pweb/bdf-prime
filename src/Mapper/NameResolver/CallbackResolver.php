<?php

namespace Bdf\Prime\Mapper\NameResolver;

use Closure;

/**
 * CallbackResolver
 */
class CallbackResolver implements ResolverInterface
{
    /**
     * @var Closure
     */
    protected $resolver;
    
    /**
     * @var Closure
     */
    protected $reverser;
    
    /**
     * @param Closure $resolver
     * @param Closure $reverser
     */
    public function __construct(Closure $resolver, Closure $reverser)
    {
        $this->resolver = $resolver;
        $this->reverser = $reverser;
    }
    
    /**
     * {@inheritdoc}
     */
    public function resolve($entityClass)
    {
        $resolver = $this->resolver;
        return $resolver($entityClass);
    }
    
    /**
     * {@inheritdoc}
     */
    public function reverse($mapperClass)
    {
        $reverser = $this->reverser;
        return $reverser($mapperClass);
    }
}