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
    const TARRAY = 'array';
    const ARRAY_OBJECT = 'array_object';
    const JSON = 'json';
    const OBJECT = 'object';

    const BOOLEAN = 'boolean';
    const TINYINT = 'tinyint';
    const SMALLINT = 'smallint';
    const INTEGER = 'integer';
    const BIGINT = 'bigint';
    const DOUBLE = 'double';
    const FLOAT = 'float';
    const DECIMAL = 'decimal';

    const STRING = 'string';
    const TEXT = 'text';
    const BLOB = 'blob';
    const BINARY = 'binary';
    const GUID = 'guid';

    const DATETIME = 'datetime';
    const DATETIMETZ = 'datetimetz';
    const DATE = 'date';
    const TIME = 'time';
    const TIMESTAMP = 'timestamp';

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
    public function name();

    /**
     * Get the php type name that map this type
     *
     * @return string
     */
    public function phpType();
}
