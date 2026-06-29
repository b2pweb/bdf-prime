<?php

namespace Bdf\Prime\Record;

use Attribute;

/**
 * Mark a constructor parameter to be filled by a relation loading
 *
 * Usage:
 * ```php
 * class MyRecord
 * {
 *     public function __construct(
 *         // Load the 'parameters' relation
 *         #[LoadRelation('parameters')]
 *         public readonly array $parameters;
 *
 *         // The relation class name can be used if not ambiguous
 *         #[LoadRelation(MyEntity::class)]
 *         public readonly MyEntity $entity,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class LoadRelation
{
    public function __construct(
        /**
         * The relation name to load
         * Can be the relation class name if not ambiguous
         */
        public readonly string $relation,
    ) {
    }
}
