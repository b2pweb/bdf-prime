<?php

namespace Bdf\Prime\Bus\Query\Configurator;

use Attribute;
use Bdf\Prime\Query\Contract\Orderable;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Sort the results of the query by the given field
 * Multiple attributes can be used to define orders on multiple fields.
 *
 * Usage:
 * ```php
 * #[PrimeQuery(MyEntity::class), OrderBy('name', 'ASC')]
 * class OrderByNameQuery
 * {
 *     // ...
 * }
 * ```
 *
 * @see Orderable::addOrder() The actual method used to add the order
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class OrderBy implements GlobalQueryConfiguratorInterface
{
    public function __construct(
        /**
         * The field name to sort by
         */
        public readonly string $field,

        /**
         * Define the sort direction
         *
         * - Use 'ASC' for ascending order (lowest first)
         * - Use 'DESC' for descending order (highest first)
         *
         * @var 'ASC'|'DESC'
         */
        public readonly string $direction = 'ASC',
    ) {}

    /**
     * {@inheritdoc}
     */
    public function configureQueryForDto(ReadCommandInterface $query, object $dto): ReadCommandInterface
    {
        /** @var Orderable&ReadCommandInterface $query */
        return $query->addOrder($this->field, $this->direction);
    }
}
