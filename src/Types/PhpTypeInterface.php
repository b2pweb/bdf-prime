<?php

namespace Bdf\Prime\Types;

/**
 * PHP type representation
 *
 * @todo rename to PhpType and make it as class or enum
 */
interface PhpTypeInterface
{
    const OBJECT = '\stdClass';
    const DATETIME = '\DateTime';
    const DATETIME_IMMUTABLE = '\DateTimeImmutable';
    const TARRAY = 'array';

    const BOOLEAN = 'boolean';
    const INTEGER = 'integer';
    const DOUBLE = 'double';
    const STRING = 'string';
}
