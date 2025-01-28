<?php

namespace Bdf\Prime\Bus\Query\Configurator;

use Attribute;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Define the number of results to skip on the query
 *
 * This attribute can be used on the query DTO class or on a property.
 * When used on the class, the offset value will be constant.
 *
 * When used on property, the property value will be used as offset.
 * If a value is specified on the attribute, it will act as default value.
 *
 * When both class and property attributes are used, the behavior will be undefined.
 *
 * Note: this attribute is not compatible with paginator and walker.
 *
 * Usage:
 * ```php
 * // Skip the first 50 results
 * #[PrimeQuery(MyEntity::class), Offset(50)]
 * class MyQuery
 * {
 *     // Define criteria ...
 * }
 *
 * // Offset defined by the property value
 * #[PrimeQuery(MyEntity::class)]
 * class MyQuery
 * {
 *     public function __construct(
 *         // Define criteria ...
 *
 *         // Offset defined by the property value. If null, the offset will be 50
 *         #[Offset(50)]
 *        public ?int $offset = null
 *     ) {}
 * }
 * ```
 *
 * @see Limitable::offset() The actual query method called
 * @see Limit To define the number of rows to retrieve
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class Offset implements PropertyQueryConfiguratorInterface, GlobalQueryConfiguratorInterface, ExecutionOptionInterface
{
    public function __construct(
        /**
         * Define the number of results to skip
         *
         * If the attribute is placed on the query DTO class, the value will always be applied.
         * If the attribute is placed on a property, the value will act as default value, and only applied if the property is null.
         * If the property is not null, the property value will be used.
         *
         * @var non-negative-int|null
         */
        public readonly ?int $offset = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function executionOptions(mixed $propertyValue = null): array
    {
        return ['offset' => $propertyValue ?? $this->offset];
    }

    /**
     * {@inheritdoc}
     */
    public function configureQueryForDto(ReadCommandInterface $query, object $dto): ReadCommandInterface
    {
        /** @var Limitable&ReadCommandInterface $query */
        return $query->offset($this->offset);
    }

    /**
     * {@inheritdoc}
     */
    public function configureQueryForProperty(ReadCommandInterface $query, mixed $propertyValue): ReadCommandInterface
    {
        /** @var Limitable&ReadCommandInterface $query */
        return $query->offset($propertyValue ?? $this->offset);
    }
}
