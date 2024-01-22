<?php

namespace Bdf\Prime\Connection\Middleware\Explain\Report;

use Bdf\Prime\Connection\Middleware\Explain\ExplainResult;

use function debug_backtrace;
use function str_starts_with;

/**
 * Handle report of explain result
 */
final class ExplainReporter
{
    /**
     * @var list<ExplainReporterInterface>
     */
    private array $reporters;

    /**
     * @param list<ExplainReporterInterface> $reporters
     */
    public function __construct(array $reporters)
    {
        $this->reporters = $reporters;
    }

    /**
     * Report an explain result
     *
     * @param string $query The executed query
     * @param ExplainResult $result The explain result
     *
     * @return void
     */
    public function report(string $query, ExplainResult $result): void
    {
        $stacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $file = $line = null;

        // Find the file and line of the first prime call
        foreach ($stacktrace as $i => $trace) {
            $class = $trace['class'] ?? null;

            // Ignore prime and doctrine calls
            if ($class && (
                str_starts_with($class, 'Bdf\Prime')
                || str_starts_with($class, 'Doctrine\\')
            )) {
                continue;
            }

            // Get the file and line of the previous call (the one who called prime)
            $file = $stacktrace[$i - 1]['file'] ?? null;
            $line = $stacktrace[$i - 1]['line'] ?? null;
            break;
        }

        foreach ($this->reporters as $reporter) {
            $reporter->report($query, $result, $file, $line);
        }
    }
}
