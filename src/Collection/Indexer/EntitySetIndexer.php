<?php

namespace Bdf\Prime\Collection\Indexer;

use Bdf\Prime\Mapper\Mapper;

/**
 * Indexer for entities, but ensure that there is no duplicates
 *
 * @template E as object
 * @implements EntityIndexerInterface<E>
 */
final class EntitySetIndexer implements EntityIndexerInterface
{
    /**
     * @var Mapper<E>
     */
    private $mapper;

    /**
     * All entities, indexing by there object hash
     *
     * @var E[]
     */
    private $entities = [];

    /**
     * The inner indexer
     *
     * @var EntityIndexer<E>|null
     */
    private $indexer;


    /**
     * EntityIndexer constructor.
     *
     * @param Mapper<E> $mapper The entity mapper. Used for extract attribute value
     */
    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Push the entity to the indexer
     * Active indexes will be updated
     *
     * @param E $entity Entity to add
     *
     * @return void
     */
    public function push($entity): void
    {
        $hash = spl_object_hash($entity);

        if (isset($this->entities[$hash])) {
            return;
        }

        $this->entities[$hash] = $entity;

        if ($this->indexer) {
            $this->indexer->push($entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function by(string $key): array
    {
        return $this->indexer()->by($key);
    }

    /**
     * {@inheritdoc}
     */
    public function byOverride(string $key): array
    {
        return $this->indexer()->byOverride($key);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return array_values($this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function empty(): bool
    {
        return empty($this->entities);
    }

    /**
     * Get or create the inner indexer
     *
     * @return EntityIndexer<E>
     * @psalm-assert EntityIndexer<E> $this->indexer
     */
    private function indexer()
    {
        if ($this->indexer) {
            return $this->indexer;
        }

        return $this->indexer = EntityIndexer::fromArray($this->mapper, array_values($this->entities));
    }
}
