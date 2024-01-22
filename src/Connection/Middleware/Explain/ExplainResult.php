<?php

namespace Bdf\Prime\Connection\Middleware\Explain;

/**
 * Store the full result of an explain query
 */
final class ExplainResult
{
    /**
     * List of tables used by the query.
     *
     * @var list<string>
     */
    public array $tables = [];

    /**
     * List of indexes used by the query.
     *
     * @var list<string>
     */
    public array $indexes = [];

    /**
     * If true, the query is performed on a covering index.
     * This means the index contains all the requested columns, and the database does not need to read the table.
     * So, the query is faster.
     *
     * Note: If this value is true, it does not mean that the query use an index for filter rows. So a which rely only
     *       on indexed columns may have covering = true, but type = scan when the criteria cannot be used on the index (for example, LIKE %xxx%).
     *
     * @var bool
     */
    public bool $covering = false;

    /**
     * If true, the query is performed on a temporary table.
     * This can occur when the query use a subquery, or a GROUP BY, or sorting on a non-indexed column.
     *
     * This is generally bad for performance, and catastrophic if the query type is scan.
     *
     * @var bool
     */
    public bool $temporary = false;

    /**
     * The query type
     * Should be one of the QueryType::* constant
     *
     * @var QueryType::*
     */
    public string $type = QueryType::UNDEFINED;

    /**
     * The number of scanned rows.
     * This is an estimation, and may be wrong.
     *
     * If the DBMS does not provide this value, it will be null.
     *
     * This value is the sum of all {@see ExplainStep::$rows}.
     *
     * @var int|null
     */
    public ?int $rows = null;

    /**
     * List of parsed steps of the query
     *
     * @var list<ExplainStep>
     */
    public array $steps = [];

    /**
     * The raw explain result, from the DBMS.
     *
     * @var array
     */
    public array $raw = [];
}
