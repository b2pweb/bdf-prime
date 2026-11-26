<?php

namespace Bdf\Prime\Record;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\Contract\Projectionable;
use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Base type for transform raw data from database to record object
 */
interface RecordHydratorInterface
{
    /**
     * Resolve columns or expressions to return from the query result
     *
     * Those values will be passed to {@see Projectionable::project()}.
     * If this method return null, the projection will be the default one (all columns).
     *
     * @param class-string $recordClass The record class name
     *
     * @return array<int|string, string|ExpressionInterface>|null
     */
    public function projection(string $recordClass): ?array;

    /**
     * Transform the DBAL result to perform preloading of content, or apply some transformations on rows
     *
     * This method is called before {@see instantiate()}, so all data added by this method will be available for instantiation.
     *
     * @param class-string $recordClass The record class name
     * @param array<array<string, mixed>> $rows The rows to prepare
     *
     * @return array<array<string, mixed>>
     */
    public function prepare(string $recordClass, array $rows): array;

    /**
     * Instantiate a record object for the given data
     *
     * @param class-string<R> $recordClass The record class name
     * @param array<string, mixed> $data The row data
     * @param PlatformInterface $platform Current DB platform. Used to resolve types
     *
     * @return R
     *
     * @template R as object
     */
    public function instantiate(string $recordClass, array $data, PlatformInterface $platform): object;

    /**
     * Apply post-processing on the entities
     * This method can be used to load relations on ORM layer
     *
     * @param class-string<R> $recordClass The record class name
     * @param array<R> $entities The entities to finalize
     * @param array<array<string, mixed>> $rows Raw database rows
     *
     * @return array Resulting entities. Usually the same as input entities
     *
     * @template R as object
     */
    public function finalize(string $recordClass, array $entities, array $rows): array;
}
