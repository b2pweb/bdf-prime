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
    public function __construct(string $suffix = 'Mapper')
    {
        $this->suffix = $suffix;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $entityClass): string
    {
        return $entityClass . $this->suffix;
    }

    /**
     * {@inheritdoc}
     */
    public function reverse(string $mapperClass): string
    {
        return substr($mapperClass, 0, -1 * mb_strlen($this->suffix));
    }
}
