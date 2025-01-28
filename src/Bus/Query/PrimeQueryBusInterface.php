<?php

namespace Bdf\Prime\Bus\Query;

use Bdf\Prime\Bus\Command\PrimeCommandBusInterface;
use Bdf\Prime\Bus\Query\ReturnType\ReturnTypeInterface;

/**
 * Base type for perform prime query from a bus
 *
 * This bus follows the CQRS pattern, and should be used for read queries only.
 * The term "query" here refers to a DTO object that represents a read operation, which usually contains
 * properties that are used as filters parameters for the actual query.
 * Queries will be executed using a query handler, resolved using the DTO class name.
 * A default query handle should be used when no specific handler are not associated with the DTO class.
 *
 * Queries DTOs should not contain any logic, and should be immutable. If you want to add logic to a query execution,
 * define a custom query handler for the query class.
 *
 * @see PrimeCommandBusInterface For perform write operations
 */
interface PrimeQueryBusInterface
{
    /**
     * Execute a read query and return the result
     *
     * The query is executed synchronously, and the result is returned directly.
     * To make the call asynchronous, you must use a specific query handler and manually handle the async logic.
     *
     * Usage:
     * ```php
     * // Instantiate the query DTO
     * $query = new MyQuery('param1', 'param2');
     *
     * // Simple query execution. The return type is not specified, so the result will be returned as is.
     * // And you should cast the result yourself.
     * $result = $bus->query($query);
     *
     * // Query execution with a return type. The type of the result will be check to ensure it matches the expected type.
     * // The type may be used to result the repository, if supported by the query handler.
     * // Note: this usage only handle single row result.
     * $result = $bus->query($query, MyEntity::class);
     *
     * // Query execution with a return type object.
     * // The return type is used to cast the raw query result to the expected type.
     * // In this example, the result will be an array of MyEntity objects.
     * $result = $bus->query($query, ArrayReturnType::of(MyEntity::class));
     * ```
     *
     * @param object $query The query DTO. A query handler can be explicitly associated with the class of the DTO, otherwise a default handler will be used.
     * @param class-string<T>|ReturnTypeInterface<T>|null $returnType The expected return type of the query. It can be used to choose the data source (e.g. repository) if it's supported by the query handler.
     *
     * @return T|null The result of the query. If a return type is provided, the result will be cast to this type.
     *
     * @template T Type of the result
     */
    public function query(object $query, string|ReturnTypeInterface|null $returnType = null): mixed;
}
