<?php

namespace Bdf\Prime\Bus\Query\Configurator;

use Attribute;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Define the limit and offset of the query
 *
 * This attribute can be used on the query DTO class or on a property.
 * When used on the class, the limit and offset values will be constant.
 *
 * When used on property, the property value will be used as limit, and the offset will be constant.
 * If a value is specified on the attribute, it will act as default value.
 *
 * When both class and property attributes are used, the behavior will be undefined.
 *
 * Example:
 * ```php
 * // Global limit. Number of result will always be limited to 50
 * #[PrimeQuery(MyEntity::class), Limit(50)]
 * class MyQuery
 * {
 *     // Define criteria ...
 * }
 *
 * // Limit defined by the property value
 * #[PrimeQuery(MyEntity::class)]
 * class MyQuery
 * {
 *     public function __construct(
 *         // Define criteria ...
 *
 *         // Limit defined by the property value. If null, the limit will be 50
 *         #[Limit(50)]
 *        public ?int $limit = null
 *     ) {}
 * }
 * ```
 *
 * @see Limitable::limit()
 * @see Limitable::offset()
 *
 * @see Offset To define the number of rows to skip, configured by the property value
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class Limit implements PropertyQueryConfiguratorInterface, GlobalQueryConfiguratorInterface, ExecutionOptionInterface
{
    public function __construct(
        /**
         * Define the number of results to return
         *
         * If the attribute is placed on the query DTO class, the value will always be applied.
         * If the attribute is placed on a property, the value will act as default value, and only applied if the property is null.
         * If the property is not null, the property value will be used.
         *
         * @var positive-int|null
         */
        public readonly ?int $limit = null,

        /**
         * Define the number of results to skip
         *
         * The behavior is the same when the attribute is placed on the query DTO class or on a property.
         * The property value is never used for the offset.
         *
         * Note: offset is not supported by the paginator. Use {@see Page} instead.
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
        $options = ['limit' => $propertyValue ?? $this->limit];

        if ($this->offset !== null) {
            $options['offset'] = $this->offset;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function configureQueryForDto(ReadCommandInterface $query, object $dto): ReadCommandInterface
    {
        /** @var Limitable&ReadCommandInterface $query */
        if ($this->limit !== null) {
            $query->limit($this->limit);
        }

        if ($this->offset !== null) {
            $query->offset($this->offset);
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function configureQueryForProperty(ReadCommandInterface $query, mixed $propertyValue): ReadCommandInterface
    {
        /** @var Limitable&ReadCommandInterface $query */
        $query->limit($propertyValue ?? $this->limit);

        if ($this->offset !== null) {
            $query->offset($this->offset);
        }

        return $query;
    }
}
