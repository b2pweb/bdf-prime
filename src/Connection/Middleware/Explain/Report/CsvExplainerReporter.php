<?php

namespace Bdf\Prime\Connection\Middleware\Explain\Report;

use Bdf\Prime\Connection\Middleware\Explain\ExplainResult;

use RuntimeException;

use function count;
use function dirname;
use function fopen;
use function implode;
use function is_dir;
use function mkdir;
use function time;

/**
 * Report bad queries to a CSV file
 *
 * Each line represents a query, with the following columns:
 * - query: The query string
 * - file: The file where the query is executed (can be empty)
 * - line: The line where the query is executed (can be empty)
 * - type: The query type (see QueryType::* constants)
 * - tables: The tables used by the query (separated by space, can be empty)
 * - indexes: The indexes used by the query (separated by space, can be empty)
 * - covering: Whether the query use a covering index (0 or 1)
 * - temporary: Whether the query use a temporary table (0 or 1)
 * - rows: The number of rows returned by the query (can be empty)
 */
final class CsvExplainerReporter implements ExplainReporterInterface
{
    /**
     * The CSV file path
     */
    private string $file;

    /**
     * Filter explain results to write
     *
     * @var (callable(ExplainResult):bool)|null
     */
    private $filter;

    /**
     * Write reports to the file when the pending reports count reach this value
     * If null, reports will be written only on flush() call, or on destruct
     */
    private ?int $autoFlushCount;

    /**
     * Write reports to the file after this interval (in seconds)
     * If null, reports will be written only on flush() call, or on destruct
     */
    private ?int $autoFlushInterval;

    /**
     * The file handle resource
     *
     * @var resource|null
     */
    private $handle = null;

    /**
     * List of pending rows to write
     *
     * @var list<list<string>>
     */
    private array $pending = [];

    /**
     * Time of the last flush (use {@see time()})
     */
    private ?int $lastFlush = null;

    /**
     * @param string $file The CSV file path
     * @param (callable(ExplainResult):bool)|null $filter The filter to apply on reports. If null, all reports are written
     * @param int|null $autoFlushCount Write reports to the file when the pending reports count reach this value. If null, reports will be written only on flush() call, or on destruct
     * @param int|null $autoFlushInterval Write reports to the file after this interval (in seconds). If null, reports will be written only on flush() call, or on destruct
     */
    public function __construct(string $file, ?callable $filter = null, ?int $autoFlushCount = null, ?int $autoFlushInterval = null)
    {
        $this->file = $file;
        $this->filter = $filter;
        $this->autoFlushCount = $autoFlushCount;
        $this->autoFlushInterval = $autoFlushInterval;
    }

    /**
     * {@inheritdoc}
     */
    public function report(string $query, ExplainResult $result, ?string $file = null, ?int $line = null): void
    {
        if ($this->filter && !($this->filter)($result)) {
            return;
        }

        $this->pending[] = [
            $query,
            $file ?? '',
            $line === null ? '' : (string) $line,
            $result->type,
            $result->tables ? implode(' ', $result->tables) : '',
            $result->indexes ? implode(' ', $result->indexes) : '',
            $result->covering ? '1' : '0',
            $result->temporary ? '1' : '0',
            $result->rows === null ? '' : (string) $result->rows,
        ];

        if ($this->shouldFlush()) {
            $this->flush();
        }
    }

    /**
     * Flush the file on destruct
     */
    public function __destruct()
    {
        $this->flush();
    }

    /**
     * Flush all pending reports to the file
     *
     * @return void
     */
    public function flush(): void
    {
        if (!$this->pending) {
            return;
        }

        if ($this->handle === null) {
            $dir = dirname($this->file);

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $handle = @fopen($this->file, 'a');

            if ($handle === false) {
                throw new RuntimeException('Unable to open file ' . $this->file . ': ' . error_get_last()['message']);
            }

            $this->handle = $handle;
        }

        flock($this->handle, LOCK_EX);

        try {
            foreach ($this->pending as $row) {
                fputcsv($this->handle, $row);
            }

            $this->pending = [];
            $this->lastFlush = time();
        } finally {
            flock($this->handle, LOCK_UN);
        }
    }

    private function shouldFlush(): bool
    {
        if ($this->autoFlushCount && count($this->pending) >= $this->autoFlushCount) {
            return true;
        }

        if ($this->autoFlushInterval === null) {
            return false;
        }

        if ($this->lastFlush === null) {
            $this->lastFlush = time();
            return false;
        }

        return time() - $this->lastFlush >= $this->autoFlushInterval;
    }
}
