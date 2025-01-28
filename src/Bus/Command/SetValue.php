<?php

namespace Bdf\Prime\Bus\Command;

use Attribute;

/**
 * Attribute for mark property as value to set on update query
 *
 * Usage:
 * ```php
 * #[PrimeUpdateCommand(User::class)]
 * class MyCommand
 * {
 *     public function __construct(
 *         // ...
 *
 *         // Use SetValue attribute to configure the SET clause
 *         #[SetValue]
 *         public readonly bool $enabled = false,
 *
 *         // You can ignore null values by setting the "skipNull" option to true
 *         // By default null values are included in the SET clause
 *         #[SetValue(skipNull: true)]
 *         public readonly ?string $reason,
 *
 *         // Expression can be used as value, with addition of transformer if you want to transform the value to an SQL expression
 *         #[SetValue(transformer: [self::class, 'addToLogExpression'])]
 *         public readonly string $log,
 *     ) {}
 *
 *      public static function addToLogExpression(string $value): ExpressionInterface
 *     {
 *         return new Attribute('logs', '%s || "' . addslaches($value) . '"');
 *     }
 * }
 *  ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class SetValue
{
    public function __construct(
        /**
         * The field name to set
         * If null, the property name will be used
         */
        public readonly ?string $field = null,

        /**
         * Does null values should be ignored?
         *
         * If true, when the property value is null, the field will not be set
         * Otherwise, the field will be set to null
         */
        public readonly bool $skipNull = false,

        /**
         * Transform the property value to an expression, or to another value.
         * This is useful to create an expression from a property value, for example to push a value into an array.
         *
         * @var (callable(mixed):mixed)|null
         */
        public readonly mixed $transformer = null,
    ) {}

    /**
     * Transform the property value to the DBAL or expression value
     *
     * @param mixed $propertyValue The original property value
     * @return mixed
     */
    public function value(mixed $propertyValue): mixed
    {
        if ($this->transformer) {
            return ($this->transformer)($propertyValue);
        }

        return $propertyValue;
    }
}
