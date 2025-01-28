<?php

namespace Bdf\Prime\Bus\Query;

use Attribute;
use Bdf\Prime\Relations\RelationInterface;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Create the query from a relation of the entity specified on the property
 *
 * When this attribute is used, {@see PrimeQuery::$entity} is ignored.
 * Only one relation can be used per query. If multiple properties are annotated with this attribute, the behavior is undefined.
 *
 * Usage:
 * ```php
 * #[PrimeQuery]
 * class MyQuery
 * {
 *     public function __construct(
 *         // Will search on the 'users' relation of the Customer entity
 *         #[FromRelation('users')]
 *         public readonly Customer $customer,
 *
 *         #[Criterion]
 *         public string $name,
 *     ) {}
 * }
 *
 * // Will get all users linked to the given customer with the name 'John'
 * // This is equivalent to `$customer->relation('users')->where('name', 'John')->all()`
 * $users = $bus->execute(new MyQuery($customer, 'John'));
 * ```
 *
 * @see RepositoryInterface::relation()
 * @see RelationInterface::link()
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class FromRelation
{
    public function __construct(
        /**
         * The relation name to query from
         * The relation entity class name can also be used, if it's not ambiguous
         */
        public readonly string $relation,
    ) {}
}
