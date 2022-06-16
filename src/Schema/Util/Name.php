<?php

namespace Bdf\Prime\Schema\Util;

/**
 * Utilities for names
 */
final class Name
{
    public const DEFAULT_LENGTH = 63;

    /**
     * Generate an identifier, using Doctrine algo
     *
     * @param string $prefix Prefix of the name
     * @param array $parts Parts of the name (i.e. target columns + table)
     * @param int $length max length of the name
     *
     * @return string
     */
    public static function generate(string $prefix, array $parts, int $length = self::DEFAULT_LENGTH)
    {
        $hash = implode('', array_map(function ($part) {
            return dechex(crc32($part));
        }, $parts));

        return substr(strtoupper($prefix.'_'.$hash), 0, $length);
    }

    /**
     * Generate a name using serialized data
     *
     * @param string $prefix
     * @param mixed $data
     * @param int $length
     *
     * @return string
     */
    public static function serialized(string $prefix, $data, int $length = self::DEFAULT_LENGTH)
    {
        return substr(strtoupper($prefix.'_'.md5(serialize($data))), 0, $length);
    }
}
