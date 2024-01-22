<?php

namespace Bdf\Prime\Connection\Middleware\Explain\Report;

use Bdf\Prime\Connection\Middleware\Explain\ExplainResult;

use RuntimeException;

use function dirname;
use function fopen;
use function implode;
use function is_dir;
use function mkdir;

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
final class CsvExplainerReport implements ExplainReporterInterface
{
    /**
     * The CSV file path
     */
    private string $file;

    /**
     * Whether the file should be flushed after each report
     */
    private bool $flush;

    /**
     * Filter explain results to write
     *
     * @var (callable(ExplainResult):bool)|null
     */
    private $filter;

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
     * @param string $file The CSV file path
     * @param bool $flush Whether the file should be flushed after each report
     * @param (callable(ExplainResult):bool)|null $filter The filter to apply on reports. If null, all reports are written
     */
    public function __construct(string $file, bool $flush = false, ?callable $filter = null)
    {
        $this->file = $file;
        $this->flush = $flush;
        $this->filter = $filter;
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

        if ($this->flush) {
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
        } finally {
            flock($this->handle, LOCK_UN);
        }
    }
}
