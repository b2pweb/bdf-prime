<?php

namespace Bdf\Prime\Collection\Indexer;

use Bdf\Prime\Mapper\Mapper;

/**
 * Base implementation of EntityIndexer
 *
 * @template E as object
 * @implements EntityIndexerInterface<E>
 */
final class EntityIndexer implements EntityIndexerInterface
{
    /**
     * @var Mapper<E>
     */
    private $mapper;

    /**
     * All indexed entities
     *
     * @var E[]
     */
    private $entities = [];

    /**
     * Map of indexes
     * Indexes are indexed by the key name, and store entities in mode "group by combine"
     *
     * @var E[][][]
     */
    private $indexed = [];


    /**
     * EntityIndexer constructor.
     *
     * @param Mapper<E> $mapper The entity mapper. Used for extract attribute value
     * @param list<string> $indexes List of initial indexes keys to use. Entities will be indexed with theses keys when pushed
     */
    public function __construct(Mapper $mapper, $indexes = [])
    {
        $this->mapper = $mapper;
        $this->indexed = array_fill_keys($indexes, []);
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
        $this->entities[] = $entity;

        foreach ($this->indexed as $key => &$indexed) {
            $indexed[$this->mapper->extractOne($entity, $key)][] = $entity;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function by(string $key): array
    {
        if (isset($this->indexed[$key])) {
            return $this->indexed[$key];
        }

        $indexed = [];

        foreach ($this->entities as $entity) {
            $indexed[$this->mapper->extractOne($entity, $key)][] = $entity;
        }

        return $this->indexed[$key] = $indexed;
    }

    /**
     * {@inheritdoc}
     */
    public function byOverride(string $key): array
    {
        $result = [];

        foreach ($this->by($key) as $key => $value) {
            $result[$key] = end($value);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->entities;
    }

    /**
     * {@inheritdoc}
     */
    public function empty(): bool
    {
        return empty($this->entities);
    }

    /**
     * Create an indexer with list of entities
     *
     * @param Mapper<T> $mapper
     * @param T[] $entities
     *
     * @return EntityIndexer
     *
     * @template T as object
     */
    public static function fromArray(Mapper $mapper, array $entities): self
    {
        $indexer = new EntityIndexer($mapper);
        $indexer->entities = $entities;

        return $indexer;
    }
}
