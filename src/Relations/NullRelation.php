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
    public function setLocalAlias(?string $localAlias)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function load(EntityIndexerInterface $collection, array $with = [], $constraints = [], array $without = []): void
    {
        // No-op
    }

    /**
     * {@inheritdoc}
     */
    public function loadIfNotLoaded(EntityIndexerInterface $collection, array $with = [], $constraints = [], array $without = []): void
    {
        // No-op
    }

    /**
     * {@inheritdoc}
     */
    public function link($owner): ReadCommandInterface
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
    public function joinRepositories(EntityJoinable $query, string $alias, $discriminator = null): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function associate($owner, $entity)
    {
        // No-op
        return $owner;
    }

    /**
     * {@inheritdoc}
     */
    public function dissociate($owner)
    {
        // No-op
        return $owner;
    }

    /**
     * {@inheritdoc}
     */
    public function add($owner, $related): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function create($owner, array $data = [])
    {
        throw new \BadMethodCallException('There is no linked entity on relation entity');
    }

    /**
     * {@inheritdoc}
     */
    public function saveAll($owner, array $relations = []): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll($owner, array $relations = []): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isLoaded($entity): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clearInfo($entity): void
    {
        // No-op
    }
}
