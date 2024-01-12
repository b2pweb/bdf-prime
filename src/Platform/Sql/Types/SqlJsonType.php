<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Exception\TypeException;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\JsonType;
use Bdf\Prime\Types\PhpTypeInterface;
use Doctrine\DBAL\Types\Types;

use function json_decode;
use function json_encode;
use function json_last_error;
use function json_last_error_msg;

/**
 * Native SQL Json type
 *
 * Unlike the facade type {@see JsonType}, this type can handle any JSON value, like boolean, integer, float, string, array and object,
 * so it's not mapped to array php type.
 *
 * By default, in prime 2.2, this type is mapped to TEXT column type, but it can be mapped to native JSON column type by setting the schemaOption "use_native_json" to true.
 * You can also set the schemaOption "object_as_array" to false to get object instead of an associative array when decoding JSON object.
 *
 * @see FieldBuilder::useNativeJsonType() To set the schemaOption "use_native_json"
 * @see FieldBuilder::jsonObjectAsArray() To set the schemaOption "object_as_array"
 */
class SqlJsonType extends AbstractPlatformType
{
    public const OPTION_USE_NATIVE_JSON = 'use_native_json';
    public const OPTION_OBJECT_AS_ARRAY = 'object_as_array';

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        if ($value === null) {
            return null;
        }

        $value = json_decode($value, $fieldOptions[self::OPTION_OBJECT_AS_ARRAY] ?? true);

        if ($value === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new TypeException(self::JSON, 'Invalid JSON data : ' . json_last_error_msg());
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(ColumnInterface $column)
    {
        $useNativeJson = $column->options()[self::OPTION_USE_NATIVE_JSON] ?? null;

        if ($useNativeJson === true) {
            return Types::JSON;
        }

        if ($useNativeJson === null) {
            @trigger_error(
                'Since prime 2.2, when using SQL "json" type the schemaOption "use_native_json" should be set to true to use native JSON column type, 
                or false to keep the the legacy behavior using TEXT column type. The current default value is false, but will be changed to true in prime 3.0.',
                E_USER_DEPRECATED
            );
        }

        return Types::TEXT;
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return PhpTypeInterface::MIXED;
    }
}
