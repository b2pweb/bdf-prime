<?php

namespace Bdf\Prime\Connection\Middleware\Explain;

/**
 * The type of query, indicating the query performance
 */
final class QueryType
{
    /**
     * The query perform only constants operations, or on constant tables.
     * This is the fastest query type.
     *
     * MySQL may consider a query on primary key as a const query.
     */
    public const CONST = 'const';

    /**
     * The query is performed on a primary index, or on a unique index.
     */
    public const PRIMARY = 'primary';

    /**
     * The query use an index for filter rows, using range or in for example.
     * This query type is generally fast, if the matching rows are low (i.e. most of the rows are filtered).
     */
    public const INDEX = 'index';

    /**
     * The query use a full table scan.
     * This is the slowest query type.
     *
     * This type of query should be avoided in production, or cached.
     *
     * Note: this type of query always occurs on SQLite if the query is a join.
     */
    public const SCAN = 'scan';

    /**
     * The query type cannot be determined.
     */
    public const UNDEFINED = 'undefined';

    /**
     * Check if the first is slower than the second (strict)
     * So, if the method return true, the first query type is slower than the second
     *
     * In case of undefined query type, the method return false
     *
     * @param self::* $first
     * @param self::* $second
     *
     * @return bool
     */
    public static function isSlower(string $first, string $second): bool
    {
        if ($first === self::UNDEFINED || $second === self::UNDEFINED) {
            return false;
        }

        return self::ordinal($first) > self::ordinal($second);
    }

    /**
     * Select the worst query type between two
     * Use this method to determine the query type of a join
     *
     * @param self::* $first
     * @param self::* $second
     *
     * @return self::*
     */
    public static function worst(string $first, string $second): string
    {
        if ($first === self::UNDEFINED) {
            return $second;
        }

        if ($second === self::UNDEFINED) {
            return $first;
        }

        return self::ordinal($first) > self::ordinal($second) ? $first : $second;
    }

    private static function ordinal(string $type): int
    {
        switch ($type) {
            case self::CONST:
                return 0;
            case self::PRIMARY:
                return 1;
            case self::INDEX:
                return 2;
            case self::SCAN:
                return 3;
            default:
                return -1;
        }
    }
}
