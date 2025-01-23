<?php

namespace Bdf\Prime\Bus\Query;

use Attribute;
use Bdf\Prime\Bus\Query\Configurator\From;
use Bdf\Prime\Bus\Query\Execution\QueryExecutionMethod;
use Bdf\Prime\Bus\Query\Execution\QueryExecutionMethodInterface;
use Bdf\Prime\Bus\Query\ReturnType\ReturnTypeInterface;
use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\Relations\EntityRelation;

/**
 * Mark the class as a query DTO
 *
 * Classes mark with this attribute can be handled by {@see DefaultPrimeQueryHandler},
 * and {@see Criterion} attributes will be used to build the query filters.
 *
 * Usage:
 * ```php
 * // Define a query to get the first entity of MyEntity matching the criteria
 * #[PrimeQuery(MyEntity::class, method: QueryExecutionMethod::First)]
 * class MyQuery
 * {
 *     public function __construct(
 *         // Define your criteria on properties
 *         #[StartsWithCriterion]
 *         public readonly string $name,
 *
 *         #[Criterion(field: 'createdAt', operator: '>=')]
 *         public readonly ?DateTime $after = null,
 *     ) {}
 * }
 *
 * $result = $bus->query(new MyQuery('John', new DateTime('2021-01-01')));
 *
 * if (!$result) {
 *     // No entity found
 * } else {
 *     // $result is an instance of MyEntity
 * }
 * ```
 *
 * @see PrimeQueryBusInterface::query()
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class PrimeQuery
{
    public function __construct(
        /**
         * The entity class name to request
         *
         * If null, the entity class name will be extracted from the return type of the query,
         * or the connection will be used and a DBAL query will be executed.
         *
         * If none of the above are set, an exception will be thrown during the execution of the query handler (unless a custom handler is used).
         *
         * @var class-string|null
         * @see ReturnTypeInterface::unwrappedType()
         */
        public readonly ?string $entity = null,

        /**
         * The connection name to used for execute the query
         *
         * If null, the connection of the entity will be used.
         * If set, without an entity class, a DBAL query will be executed on the given connection.
         * If both entity (or return type) and connection are set, the connection will be used for the entity class,
         * using {@see EntityRelation::on()} to temporary change the connection.
         *
         * If only the connection is set, use {@see From} to specify the table name.
         */
        public readonly ?string $connection = null,

        /**
         * Define the method to use for execute the query.
         *
         * You can use one of the cases of {@see QueryExecutionMethod}.
         * If you want a specific behavior, you can use your own implementation of {@see QueryExecutionMethodInterface},
         * and instantiate it in the attribute.
         */
        public readonly QueryExecutionMethodInterface $method = QueryExecutionMethod::All,
    ) {}
}
