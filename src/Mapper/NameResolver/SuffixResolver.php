<?php

namespace Bdf\Prime\Mapper\NameResolver;

/**
 * @package Bdf\Prime\Mapper\NameResolver
 */
class SuffixResolver implements ResolverInterface
{
    /**
     * @var string
     */
    protected $suffix;
    
    /**
     * @param string $suffix
     */
    public function __construct($suffix = 'Mapper')
    {
        $this->suffix = $suffix;
    }
    
    /**
     * {@inheritdoc}
     */
    public function resolve($entityClass)
    {
        return $entityClass . $this->suffix;
    }
    
    /**
     * {@inheritdoc}
     */
    public function reverse($mapperClass)
    {
        return substr($mapperClass, 0, -1 * mb_strlen($this->suffix));
    }
}
