<?php

namespace Bdf\Prime\Bus;

use Attribute;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Define the table to select from
 *
 * Multiple from attributes can be used to join multiple tables.
 * This attribute should be used in case of DBAL queries (e.g. when no entity is defined on the query).
 *
 * Usage:
 * ```php
 * #[PrimeQuery(connection: 'other'), From('my_table', 'mt')]
 * class MyQuery
 * {
 *     // Define criteria ...
 * }
 * ```
 *
 * @see QueryInterface::from() The actual query method called by this attribute
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class From implements GlobalQueryConfiguratorInterface
{
    public function __construct(
        /**
         * The table name to select from
         */
        public string $table,

        /**
         * The table name alias on the query.
         * If null no alias will be used.
         */
        public ?string $alias = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function configureQueryForDto(ReadCommandInterface $query, object $dto): ReadCommandInterface
    {
        return $query->from($this->table, $this->alias);
    }
}
