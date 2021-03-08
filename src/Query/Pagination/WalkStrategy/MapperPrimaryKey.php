<?php

namespace Bdf\Prime\Query\Pagination\WalkStrategy;

use Bdf\Prime\Mapper\Mapper;

/**
 * Entity primary key extracted from the Mapper
 */
final class MapperPrimaryKey implements KeyInterface
{
    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * EntityPrimaryKey constructor.
     *
     * @param Mapper $mapper
     */
    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->mapper->metadata()->primary['attributes'][0];
    }

    /**
     * {@inheritdoc}
     */
    public function get($entity)
    {
        return $this->mapper->getId($entity);
    }
}
