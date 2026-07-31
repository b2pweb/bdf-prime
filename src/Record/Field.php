<?php

namespace Bdf\Prime\Record;

use Attribute;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\PlatformTypesInterface;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Override;
use ReflectionParameter;

use function assert;
use function is_string;

/**
 * Define a mapping for a database field to a record constructor parameter
 *
 * Usage:
 * ```php
 * class MyRecord
 * {
 *     public function __construct(
 *         // When the mapping is simple, the Field attribute can be omitted
 *         // Map the 'id' field from the database to the $id parameter
 *         // This is equivalent to #[Field('id', castType: CastType::Integer, nullable: false)]
 *         public readonly int $id,
 *
 *         // Map the 'full_name' field from the database to the $name parameter
 *         #[Field('full_name')]
 *         public readonly string $name,
 *
 *         // Database field type can be specified to allow parsing the value
 *         #[Field('created_at', type: 'datetime')]
 *         public readonly DateTime $createdAt,
 *
 *         // Use a transformer to parse database value
 *         #[Field(transformer: CustomData::fromString(...))]
 *         public readonly CustomData $data,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class Field implements RecordParameterInterface
{
    public function __construct(
        /**
         * The name of the field from the database result.
         *
         * In case of ORM call, the field name is the property name defined on the mapper.
         * If the value is null, the parameter name of the record will be used.
         *
         * This value will be used to map data from row to constructor parameters.
         */
        public readonly ?string $name = null,

        /**
         * Use a custom expression for the field instead of the field name.
         *
         * If an expression is set, the {@see Field::$name} value will be used as alias.
         * The expression can be a string for a simple field alias, or an instance of {@see ExpressionInterface} for a complex expression.
         *
         * @var string|ExpressionInterface|null
         */
        public readonly string|ExpressionInterface|null $expression = null,

        /**
         * The database field type.
         *
         * Will be used to parse the database value to the PHP value.
         * If null, the type will be resolved from the mapper, if possible.
         * If the type is not resolved, the raw database value will be used.
         */
        public readonly ?string $type = null,

        /**
         * Cast the database value to a specific type.
         * If null, the type will be resolved from the parameter type hint.
         */
        public readonly ?CastType $castType = null,

        /**
         * Check if the parameter is nullable.
         * If null, the value will be resolved from the parameter type hint.
         */
        public readonly ?bool $nullable = null,

        /**
         * Field used for projection (e.g. SELECT clause).
         *
         * If this value is false, the field will not be projected.
         * If this value is null, {@see Field::$name} will be used for projection.
         * If this value is a string, it will be used as alias for the projection.
         */
        public readonly string|false|null $projection = null,

        /**
         * A transformer function to apply to the field value.
         *
         * This transformer will be called with the value parsed by the prime type (if provided)
         * before passing it to the parameter.
         *
         * @var null|callable(mixed):mixed
         */
        public readonly mixed $transformer = null,
    ) {
    }

    /**
     * Cast the database value to the parameter type.
     *
     * @param mixed $value The value from the database
     * @return mixed
     */
    public function cast(mixed $value): mixed
    {
        if ($this->transformer !== null) {
            $value = ($this->transformer)($value);
        }

        if ($this->castType === null) {
            return $value;
        }

        return $this->castType->cast($value, $this->nullable ?? true);
    }

    #[Override]
    public function projection(): array
    {
        if ($this->projection === false) {
            return [];
        }

        $name = $this->projection ?? $this->name;
        assert($name !== null);

        if ($this->expression) {
            return [$name => $this->expression];
        }

        return [$name];
    }

    #[Override]
    public function value(PlatformInterface $platform, array $data): mixed
    {
        $value = $data[$this->name] ?? null;

        if ($this->type !== null) {
            $value = $platform->types()->fromDatabase($value, $this->type);
        }

        return $this->cast($value);
    }

    /**
     * Replace values and return a new instance
     */
    public function with(?string $name = null, ExpressionInterface|string|null $expression = null, ?string $type = null, ?CastType $castType = null, ?bool $nullable = null, string|false|null $projection = null, ?callable $transformer = null): self
    {
        return new self(
            name: $name ?? $this->name,
            expression: $expression ?? $this->expression,
            type: $type ?? $this->type,
            castType: $castType ?? $this->castType,
            nullable: $nullable ?? $this->nullable,
            projection: $projection ?? $this->projection,
            transformer: $transformer ?? $this->transformer,
        );
    }

    /**
     * Resolve database field name and type from the attributes metadata
     *
     * @param array $attributesMetadata
     * @return self
     */
    public function withAttributesMetadata(array $attributesMetadata): self
    {
        $field = $this;

        if ($field->type === null && ($field->expression === null || is_string($field->expression))) {
            $field = $field->with(
                type: $attributesMetadata[$field->expression ?? $field->name]['type'] ?? null,
            );
        }

        return $field->with(
            name: $attributesMetadata[$field->name]['field'] ?? $field->name,
            projection: $field->projection ?? $field->name,
        );
    }

    /**
     * Create the corresponding field from a reflection parameter
     *
     * @param ReflectionParameter $parameter
     * @return self
     */
    public static function fromReflectionParameter(ReflectionParameter $parameter): self
    {
        $field = null;

        foreach ($parameter->getAttributes(Field::class) as $attribute) {
            $field = $attribute->newInstance();
            break;
        }

        $field ??= new Field();

        return $field->with(
            name: $field->name ?? $parameter->getName(),
            castType: $field->castType ?? CastType::fromType($parameter->getType()),
            nullable: $field->nullable ?? ($parameter->getType()?->allowsNull() ?? true),
        );
    }
}
