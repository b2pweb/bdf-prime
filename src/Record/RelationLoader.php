<?php

namespace Bdf\Prime\Record;

use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Perform bulk loading of a relation
 */
final class RelationLoader
{
    public function __construct(
        /**
         * The relation name to load
         */
        public readonly string $relationName,

        /**
         * The target parameter name
         * Should match with {@see Field::$name}
         */
        public readonly string $target,

        /**
         * The property name storing the foreign key of the relation
         */
        public readonly string $foreignKeyProperty,

        /**
         * The database field name storing the foreign key of the relation
         */
        public readonly string $foreignKeyField,
    ) {
    }

    /**
     * Perform loading of relation, and attach the loaded entities to the rows
     *
     * @param RepositoryInterface $ownerRepository
     * @param array<array<string, mixed>> $rows
     *
     * @return array<array<string, mixed>>
     */
    public function load(RepositoryInterface $ownerRepository, array $rows): array
    {
        $relation = $ownerRepository->relation($this->relationName);
        $keys = [];

        foreach ($rows as $row) {
            if (($fk = $row[$this->foreignKeyField] ?? null)) {
                $keys[$fk] = $fk;
            }
        }

        $entities = $relation->loadByForeignKeys(array_values($keys));
        $loaded = $rows;

        foreach ($rows as $k => $row) {
            $fk = $row[$this->foreignKeyField] ?? null;
            $loaded[$k][$this->target] = $fk !== null ? $entities[$fk] ?? null : null;
        }

        return $loaded;
    }
}
