<?php

namespace Bdf\Prime\Bus\Query\Configurator;

use Attribute;
use Bdf\Prime\Query\Contract\Projectionable;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Define columns or expressions to return
 * This attribute will specify the SELECT clause on an SQL query.
 *
 * Usage:
 * ```php
 * // Load only the 'name' column
 * #[PrimeQuery(MyEntity::class), Projection('name')]
 * class GetNameQuery
 * {
 *     // ...
 * }
 *
 * // Load multiple columns
 * #[PrimeQuery(MyEntity::class), Projection(['name', 'value'])]
 * class GetNameAndValueQuery
 * {
 *     // ...
 * }
 *
 * // Define an alias using the key of the array
 * #[PrimeQuery(MyEntity::class), Projection(['firstName' => 'name'])]
 * class GetWithAliasQuery
 * {
 *     // ...
 * }
 *
 * // Expression can also be used
 * #[PrimeQuery(MyEntity::class), Projection(['hash' => new Attribute('content', 'MD5(%s)')])]
 * class GetHashQuery
 * {
 *     // ...
 * }
 * ```
 *
 * @see Projectionable::project() The actual method called
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Projection implements GlobalQueryConfiguratorInterface
{
    public function __construct(
        /**
         * Columns or expressions to return
         *
         * When an array is passed, the key is the column alias, and the value is the column name or expression.
         * If the key is an integer, no alias will be used, so the value will be accessible using the column name (or complete expression).
         *
         * @var string|ExpressionInterface|array<array-key, string|ExpressionInterface>
         */
        public array|string|ExpressionInterface $columns,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function configureQueryForDto(ReadCommandInterface $query, object $dto): ReadCommandInterface
    {
        /** @var Projectionable&ReadCommandInterface $query */
        return $query->project($this->columns);
    }
}
