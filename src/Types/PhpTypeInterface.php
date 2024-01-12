<?php

namespace Bdf\Prime\Types;

/**
 * PHP type representation
 *
 * @todo rename to PhpType and make it as class or enum
 */
interface PhpTypeInterface
{
    public const OBJECT = '\stdClass';
    public const DATETIME = '\DateTime';
    public const DATETIME_IMMUTABLE = '\DateTimeImmutable';
    public const TARRAY = 'array';

    public const BOOLEAN = 'boolean';
    public const INTEGER = 'integer';
    public const DOUBLE = 'double';
    public const STRING = 'string';
    public const MIXED = 'mixed';
}
