<?php

namespace Bdf\Prime\Schema;

/**
 * Resolve and create upgrader instances
 */
interface StructureUpgraderResolverInterface
{
    /**
     * Get a structure upgrader instance from a mapper class
     *
     * @param class-string $mapperClassName The mapper class name
     * @param bool $force Force usage of effective upgrader even if schema manager is disabled
     *
     * @return StructureUpgraderInterface|null Upgrader instance, or null if the class name is not a valid mapper class
     */
    public function resolveByMapperClass(string $mapperClassName, bool $force = false): ?StructureUpgraderInterface;

    /**
     * Get a structure upgrader instance from a domain class (i.e. entity)
     *
     * @param class-string $className The class name
     * @param bool $force Force usage of effective upgrader even if schema manager is disabled
     *
     * @return StructureUpgraderInterface Upgrader instance, or null if the class is not related to a repository
     */
    public function resolveByDomainClass(string $className, bool $force = false): ?StructureUpgraderInterface;
}
