<?php

namespace Bdf\Prime\Schema\Manager;

/**
 * Interface for schema manager that can generate rollback queries
 */
interface RollbackQueryManagerInterface extends QueryManagerInterface
{
    /**
     * Enable or disable the generation of rollback queries
     *
     * @param bool $enable true to enable
     *
     * @return $this
     */
    public function generateRollback(bool $enable = true);

    /**
     * Get queries generated for rollback the last migration
     *
     * @return list<mixed> Rollback queries. The type of the query depends on the platform.
     */
    public function rollbackQueries(): array;

    /**
     * Push queries for perform rollback
     *
     * If the parameter is a callable, the callable will be called with the schema manager as parameter,
     * and may return the queries, or directly push the queries to the schema manager.
     *
     * Example:
     * <code>
     * // Push directly queries
     * $schema->pushRollback('DROP TABLE `person`');
     * $schema->pushRollback(['DROP TABLE `person`', 'DROP TABLE `address`']);
     *
     * // Use schema manager as builder
     * $schema->pushRollback(function (SchemaManagerInterface $schema) {
     *     $schema->add(...);
     *     $schema->drop(...);
     * });
     *
     * // Use schema manager as builder, and return queries
     * $schema->pushRollback(fn (SchemaManagerInterface $schema) => $schema->diff(...));
     * </code>
     *
     * @param callable(static):mixed|list<mixed>|mixed $queries Rollback queries. The type of the query depends on the platform.
     *
     * @return $this
     */
    public function pushRollback($queries);
}
