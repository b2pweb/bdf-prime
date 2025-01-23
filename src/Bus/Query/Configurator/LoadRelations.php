<?php

namespace Bdf\Prime\Bus\Query\Configurator;

use Attribute;
use Bdf\Prime\Query\Query;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Load given relations on the query
 *
 * Usage:
 * ```php
 * #[PrimeQuery(User::class), LoadRelations('roles')]
 * class MyQuery
 * {
 *     public function __construct(
 *         #[StartsWithCriterion]
 *         public string $name,
 *     ) {}
 * }
 *
 * // Load all users with name starting with 'John' and their roles
 * $users = $bus->query(new MyQuery('John'));
 * ```
 *
 * @see Query::with() Actual method used to load relations
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class LoadRelations implements GlobalQueryConfiguratorInterface
{
    /**
     * @var string[]
     */
    public readonly array $relations;

    /**
     * @param string ...$relations Relation names to load. The relation class name can also be used, if unambiguous.
     */
    public function __construct(string ...$relations)
    {
        $this->relations = $relations;
    }

    /**
     * {@inheritdoc}
     */
    public function configureQueryForDto(ReadCommandInterface $query, object $dto): ReadCommandInterface
    {
        return $query->with($this->relations);
    }
}
