<?php

namespace Bdf\Prime\Mapper\Attribute;

use Attribute;
use Bdf\Prime\Entity\Criteria;
use Bdf\Prime\Mapper\Mapper;

/**
 * Define a custom criteria class for the mapper
 *
 * @see Mapper::setCriteriaClass()
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class CriteriaClass implements MapperConfigurationInterface
{
    /**
     * @var class-string<Criteria>
     */
    private string $criteriaClass;

    /**
     * @param class-string<Criteria> $criteriaClass
     */
    public function __construct(string $criteriaClass)
    {
        $this->criteriaClass = $criteriaClass;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Mapper $mapper): void
    {
        $mapper->setCriteriaClass($this->criteriaClass);
    }
}
