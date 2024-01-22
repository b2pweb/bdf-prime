<?php

namespace Bdf\Prime\Connection\Middleware\Explain\Report;

use Bdf\Prime\Connection\Middleware\Explain\ExplainResult;
use Bdf\Prime\Connection\Middleware\Explain\QueryType;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use function implode;

/**
 * Report explain result to the logger
 *
 * The log level will depends on the query performance:
 * - INFO: the query is optimized (use index, no temporary table, etc.)
 * - WARNING: the query is not optimized (use scan, temporary table, etc.) and should be reviewed
 * - ERROR: the query perform a full scan of all joined tables and should be entirely rewritten
 */
final class LoggerExplainReporter implements ExplainReporterInterface
{
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function report(string $query, ExplainResult $result, ?string $file = null, ?int $line = null): void
    {
        $message = "Explanation of query '$query'";

        if ($file !== null) {
            $message .= " in $file:$line";
        }

        $message .= ': ' . $result->type;

        if ($result->tables) {
            $message .= ' on ' . implode(', ', $result->tables);
        }

        if ($result->indexes) {
            $message .= ' using ';

            if ($result->covering) {
                $message .= 'covering ';
            }

            $message .= 'index ' . implode(', ', $result->indexes);
        }

        if ($result->temporary) {
            $message .= ' on temporary table';
        }

        if ($result->rows !== null) {
            $message .= ' (' . $result->rows . ' rows)';
        }

        $this->logger->log(
            $this->level($result),
            $message,
            [
                'query' => $query,
                'file'  => $file,
                'line'  => $line,
                'explain' => $result,
            ]
        );
    }

    private function level(ExplainResult $result): string
    {
        if ($result->type === QueryType::SCAN) {
            if ($result->covering) {
                return LogLevel::WARNING;
            }

            foreach ($result->steps as $step) {
                if ($step->type !== QueryType::UNDEFINED && $step->type !== QueryType::SCAN) {
                    return LogLevel::WARNING;
                }
            }

            return LogLevel::ERROR;
        }

        if ($result->temporary) {
            return LogLevel::WARNING;
        }

        return LogLevel::INFO;
    }
}
