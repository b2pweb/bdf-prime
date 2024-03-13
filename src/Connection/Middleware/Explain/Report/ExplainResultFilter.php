<?php

namespace Bdf\Prime\Connection\Middleware\Explain\Report;

use Bdf\Prime\Connection\Middleware\Explain\ExplainResult;
use Bdf\Prime\Connection\Middleware\Explain\QueryType;

/**
 * Callable object for a predicate on explain result
 */
final class ExplainResultFilter
{
    /**
     * Match query types that are slower than the given type
     * If null, all query types are matched
     *
     * For example, if the minimum type is QueryType::INDEX, the report will be triggered only if the query type is QueryType::SCAN
     *
     * @var QueryType::*|null
     */
    public ?string $minimumType = null;

    /**
     * If true, will match queries with type scan on all steps
     * If false, this rule is ignored
     *
     * This is different from minimumType = QueryType::INDEX, which match only if the query type is scan on a single step
     *
     * @var bool
     */
    public bool $allScan = false;

    /**
     * If true, the report will not match if the query is using a covering index
     * If false, this rule is ignored
     *
     * Note: this rule will not completely ignore query using covering index, but require the query to match other rules.
     *
     * @var bool
     */
    public bool $ignoreCovering = false;

    /**
     * Match query that scan at least the given number of rows
     * If null, or if the report does not provide the number of rows, this rule is ignored
     *
     * @var int|null
     */
    public ?int $minimumRows = null;

    /**
     * Match query that use temporary table
     *
     * @var bool
     */
    public bool $temporary = false;

    /**
     * Minimum number of criteria that must be matched to trigger the report
     * e.g. if this value is 1, only one criterion is required to trigger the report, if this value is 2, at least two criteria must be matched, etc.
     *
     * So, the value 1 can be considered as an OR, and if the value is equal to the number of criteria, it can be considered as an AND
     *
     * @var int
     */
    public int $minimumMatchingCriteria = 1;

    public function __invoke(ExplainResult $explain): bool
    {
        $matching = 0;

        if ($this->minimumType && QueryType::isSlower($explain->type, $this->minimumType)) {
            ++$matching;
        }

        if ($this->temporary && $explain->temporary) {
            ++$matching;
        }

        if ($this->minimumRows && $explain->rows && $explain->rows >= $this->minimumRows) {
            ++$matching;
        }

        if ($this->allScan) {
            $allScan = true;

            foreach ($explain->steps as $step) {
                if ($step->type !== QueryType::UNDEFINED && $step->type !== QueryType::SCAN) {
                    $allScan = false;
                    break;
                }
            }

            if ($allScan) {
                ++$matching;
            }
        }

        if ($this->ignoreCovering && $explain->covering) {
            --$matching;
        }

        return $matching >= $this->minimumMatchingCriteria;
    }
}
