<?php

namespace Bdf\Prime\Mapper\Attribute;

use Attribute;
use Bdf\Prime\Mapper\Mapper;

/**
 * Define a custom repository class for the mapper
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class RepositoryClass implements MapperConfigurationInterface
{
    /**
     * @var class-string<\Bdf\Prime\Repository\RepositoryInterface>
     */
    private string $repositoryClass;

    /**
     * @param class-string<\Bdf\Prime\Repository\RepositoryInterface> $repositoryClass
     */
    public function __construct(string $repositoryClass)
    {
        $this->repositoryClass = $repositoryClass;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Mapper $mapper): void
    {
        $mapper->setRepositoryClass($this->repositoryClass);
    }
}
