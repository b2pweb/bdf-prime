<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\ServiceLocator;

/**
 * Resolve upgrader for ORM repositories
 *
 * @see RepositoryInterface
 */
final class RepositoryUpgraderResolver implements StructureUpgraderResolverInterface
{
    private ServiceLocator $locator;

    /**
     * @param ServiceLocator $locator
     */
    public function __construct(ServiceLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveByMapperClass(string $mapperClassName, bool $force = false): ?StructureUpgraderInterface
    {
        if (!$this->locator->mappers()->isMapper($mapperClassName)) {
            return null;
        }

        return $this->locator->mappers()->createMapper($this->locator, $mapperClassName)->repository()->schema($force);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveByDomainClass(string $className, bool $force = false): ?StructureUpgraderInterface
    {
        $repository = $this->locator->repository($className);

        return $repository ? $repository->schema($force) : null;
    }
}
