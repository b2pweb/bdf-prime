<?php

namespace Bdf\Prime\Record;

use Attribute;
use Bdf\Prime\Query\ReadCommandInterface;

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
 *
 *         // The parameter type is used as relation name if not specified
 *         #[LoadRelation]
 *         public readonly OtherEntity $other,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class LoadRelation
{
    public function __construct(
        /**
         * The relation name to load
         *
         * Can be the relation class name if not ambiguous.
         * If null, the type of the parameter will be used as relation name.
         */
        public ?string $relation = null,

        /**
         * Define the read record type for the relation
         *
         * If this value is null and no transformer is set, but the parameter type differ from the relation
         * entity, the parameter type will be used as read record.
         *
         * @var class-string|null
         * @see ReadCommandInterface::as()
         */
        public ?string $as = null,

        /**
         * A transformer function to apply to the relation entity (or the read record).
         * This transformer will be called before passing it to the parameter.
         *
         * @var null|callable(mixed):mixed
         * @see Field::$transformer
         */
        public mixed $transformer = null,
    ) {
    }
}
