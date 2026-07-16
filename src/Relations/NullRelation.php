<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexerInterface;
use Bdf\Prime\Mapper\SingleTableInheritanceMapper;
use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Null object for relation
 *
 * All relation operation will be disabled, and do nothing
 * This relation can be used as placeholder on a relation of sub-mapper of {@see SingleTableInheritanceMapper}
 *
 * @template L as object
 * @template R as object
 *
 * @implements RelationInterface<L, R>
 */
final class NullRelation implements RelationInterface
{
    /**
     * @var RepositoryInterface<L>
     */
    private RepositoryInterface $repository;

    /**
     * @param RepositoryInterface<L> $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function relationRepository(): RepositoryInterface
    {
        throw new \BadMethodCallException('Null relation does not have a relation repository');
    }

    /**
     * {@inheritdoc}
     */
    public function localRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * {@inheritdoc}
     */
    public function localKeyProperty(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocalAlias(?string $localAlias): static
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options): static
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function load(EntityIndexerInterface $collection, array $with = [], iterable|callable $constraints = [], array $without = []): void
    {
        // No-op
    }

    /**
     * {@inheritdoc}
     */
    public function loadByForeignKeys(array $keys): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function loadIfNotLoaded(EntityIndexerInterface $collection, array $with = [], iterable|callable $constraints = [], array $without = []): void
    {
        // No-op
    }

    /**
     * {@inheritdoc}
     */
    public function link(array|object $owner, ?string $queryClass = null): ReadCommandInterface
    {
        throw new \BadMethodCallException('Cannot request from a null relation');
    }

    /**
     * {@inheritdoc}
     */
    public function join(EntityJoinable $query, string $alias): void
    {
        // No-op
    }

    /**
     * {@inheritdoc}
     */
    public function joinRepositories(EntityJoinable $query, string $alias, string|int|null $discriminator = null): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function associate(object $owner, object $entity): object
    {
        // No-op
        return $owner;
    }

    /**
     * {@inheritdoc}
     */
    public function dissociate(object $owner): object
    {
        // No-op
        return $owner;
    }

    /**
     * {@inheritdoc}
     */
    public function add(object $owner, object $related): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function create(object $owner, array $data = []): object
    {
        throw new \BadMethodCallException('There is no linked entity on relation entity');
    }

    /**
     * {@inheritdoc}
     */
    public function saveAll(object $owner, array $relations = []): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll(object $owner, array $relations = []): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isLoaded(object $entity): bool
    {
        return true;
    }
}
