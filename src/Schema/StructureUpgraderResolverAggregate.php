<?php

namespace Bdf\Prime\Schema;

/**
 * Aggregate of upgrader resolvers
 */
final class StructureUpgraderResolverAggregate implements StructureUpgraderResolverInterface
{
    /**
     * @var StructureUpgraderResolverInterface[]
     */
    private array $resolvers;

    /**
     * @param StructureUpgraderResolverInterface[] $resolvers
     */
    public function __construct(array $resolvers = [])
    {
        $this->resolvers = $resolvers;
    }

    /**
     * Register a new resolver on th aggregate
     * The new resolver will be added at the end, so it will be the last prioritized
     *
     * @param StructureUpgraderResolverInterface $resolver
     *
     * @return void
     */
    public function register(StructureUpgraderResolverInterface $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveByMapperClass(string $mapperClassName, bool $force = false): ?StructureUpgraderInterface
    {
        foreach ($this->resolvers as $resolver) {
            $upgrader = $resolver->resolveByMapperClass($mapperClassName, $force);

            if ($upgrader) {
                return $upgrader;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveByDomainClass(string $className, bool $force = false): ?StructureUpgraderInterface
    {
        foreach ($this->resolvers as $resolver) {
            $upgrader = $resolver->resolveByDomainClass($className, $force);

            if ($upgrader) {
                return $upgrader;
            }
        }

        return null;
    }
}
