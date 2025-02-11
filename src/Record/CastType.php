<?php

namespace Bdf\Prime\Record;

use ReflectionNamedType;
use ReflectionType;

use function filter_var;

/**
 * Enum of types supported by field for performing cast before injecting constructor parameters
 */
enum CastType: string
{
    case Mixed = 'mixed';
    case Integer = 'int';
    case Float = 'float';
    case String = 'string';
    case Array = 'array';
    case Boolean = 'bool';

    /**
     * Cast the given value to the type
     *
     * @param mixed $value The value to cast
     * @param bool $nullable If the value can be null
     *
     * @return mixed
     */
    public function cast(mixed $value, bool $nullable = false): mixed
    {
        if ($nullable && $value === null) {
            return null;
        }

        return match ($this) {
            self::Mixed => $value,
            self::Integer => $nullable ? ($value === '' || !is_numeric($value) ? null : (int) $value) : (int) $value,
            self::Float => $nullable ? ($value === '' || !is_numeric($value) ? null : (float) $value) : (float) $value,
            self::String => (string) $value,
            self::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN, $nullable ? FILTER_NULL_ON_FAILURE : 0),
            self::Array => $nullable ? ($value === '' ? null : (array) $value) : ($value === '' ? [] : (array) $value),
        };
    }

    /**
     * Resolve the cast type from the parameter reflection type
     *
     * @param ReflectionType|null $type
     * @return self
     */
    public static function fromType(?ReflectionType $type): self
    {
        if (!$type instanceof ReflectionNamedType) {
            return self::Mixed;
        }

        return self::tryFrom($type->getName()) ?? self::Mixed;
    }
}
