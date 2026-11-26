<?php

namespace Bdf\Prime\Collection\Indexer;

use Closure;

/**
 * Base implementation of EntityIndexer
 *
 * @template E as object
 * @implements EntityIndexerInterface<E>
 */
final class RecordIndexer implements EntityIndexerInterface
{
    /**
     * All indexed entities
     *
     * @var E[]
     */
    private array $entities = [];

    /**
     * Map of indexes
     * Indexes are indexed by the key name, and store entities in mode "group by combine"
     *
     * @var E[][][]
     */
    private array $indexed = [];


    /**
     * @param list<string> $indexes List of initial indexes keys to use. Entities will be indexed with theses keys when pushed
     */
    public function __construct(
        /**
         * Extract a property from the record, if the property is not publicly available.
         * Takes as first parameter the record instance, and as second the property name.
         *
         * @var Closure(E, string):mixed
         */
        private readonly Closure $extractor,
        array $indexes = []
    ) {
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
            $property = $entity->$key ?? ($this->extractor)($entity, $key);
            $indexed[$property][] = $entity;
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
            $property = $entity->$key ?? ($this->extractor)($entity, $key);
            $indexed[$property][] = $entity;
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
}
