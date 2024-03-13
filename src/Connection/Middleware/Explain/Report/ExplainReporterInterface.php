<?php

namespace Bdf\Prime\Connection\Middleware\Explain\Report;

use Bdf\Prime\Connection\Middleware\Explain\ExplainResult;

/**
 * Handle report of explain result
 * The reporter should write the explain result to a storage, or analyze it
 */
interface ExplainReporterInterface
{
    /**
     * Report an explain result
     *
     * @param string $query The executed query
     * @param ExplainResult $result The explain result
     * @param string|null $file The file where the query is executed, if available
     * @param int|null $line The line where the query is executed, if available
     *
     * @return void
     */
    public function report(string $query, ExplainResult $result, ?string $file = null, ?int $line = null): void;
}
