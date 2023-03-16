<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\Exception\PrimeException;

/**
 * Perform schema operation like migration, deletion...
 */
interface StructureUpgraderInterface
{
    /**
     * Migrate table structure changes to database
     *
     * @param bool $listDrop
     *
     * @throws PrimeException When migration fail
     */
    public function migrate(bool $listDrop = true): void;

    /**
     * List table structure changes
     *
     * @param bool $listDrop
     *
     * @return array Array of queries
     * @throws PrimeException When diff fail
     */
    public function diff(bool $listDrop = true): array;

    /**
     * List migration queries, indexed by connection name
     *
     * The result is an array with two keys:
     * - up: the queries to execute to migrate the schema
     * - down: the queries to execute to rollback the migration
     *
     * @param bool $listDrop Whether to list drop queries
     *
     * @return array{up: array<string, list<string>>, down: array<string, list<string>>}
     */
    public function queries(bool $listDrop = true): array;

    /**
     * Truncate table
     *
     * @param bool $cascade
     *
     * @return bool true on success
     * @throws PrimeException When truncate fail
     */
    public function truncate(bool $cascade = false): bool;

    /**
     * Drop table and its sequence if exists
     *
     * @return bool true on success
     * @throws PrimeException When drop fail
     */
    public function drop(): bool;
}
