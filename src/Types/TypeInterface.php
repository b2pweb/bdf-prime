<?php

namespace Bdf\Prime\Types;

/**
 * Database type representation
 *
 * Types should check if the platform native accept the type,
 * If not, it should convert-it
 */
interface TypeInterface
{
    public const TARRAY = 'array';
    public const ARRAY_OBJECT = 'array_object';
    public const JSON = 'json';
    public const OBJECT = 'object';

    public const BOOLEAN = 'boolean';
    public const TINYINT = 'tinyint';
    public const SMALLINT = 'smallint';
    public const INTEGER = 'integer';
    public const BIGINT = 'bigint';
    public const DOUBLE = 'double';
    public const FLOAT = 'float';
    public const DECIMAL = 'decimal';

    public const STRING = 'string';
    public const TEXT = 'text';
    public const BLOB = 'blob';
    public const BINARY = 'binary';
    public const GUID = 'guid';

    public const DATETIME = 'datetime';
    public const DATETIMETZ = 'datetimetz';
    public const DATE = 'date';
    public const TIME = 'time';
    public const TIMESTAMP = 'timestamp';

    /**
     * Transform database value to PHP value
     *
     * @param mixed $value
     * @param array $fieldOptions
     *
     * @return mixed
     */
    public function fromDatabase($value, array $fieldOptions = []);

    /**
     * Transform PHP value to database value.
     * The type SHOULD supports value that is already normalized to database
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function toDatabase($value);

    /**
     * Get the type name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get the php type name that map this type
     *
     * @return string
     */
    public function phpType(): string;
}
