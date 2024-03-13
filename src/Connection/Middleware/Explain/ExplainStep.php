<?php

namespace Bdf\Prime\Connection\Middleware\Explain;

/**
 * Represent an explain step
 * Generally a step is created for each joined or scanned table
 */
final class ExplainStep
{
    /**
     * The table name.
     * This value can be null if the step is not related to a table (for example a constant expression)
     *
     * @var string|null
     */
    public ?string $table = null;

    /**
     * The used index for filter the table.
     *
     * @var string|null
     */
    public ?string $index = null;

    /**
     * If true, the step is performed on a covering index.
     * This means the index contains all the requested columns, and the database does not need to read the table.
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
     * @var int|null
     */
    public ?int $rows = null;

    /**
     * Extra information or comment about the step.
     * This value does not have a specific format, and depends on the DBMS.
     *
     * @var string|null
     */
    public ?string $extra = null;
}
