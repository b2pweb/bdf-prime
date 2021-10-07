<?php

namespace Bdf\Prime\Connection\Result\FetchStrategy;

/**
 * Fetch a single column of each results
 *
 * @implements ArrayFetchStrategyInterface<mixed>
 */
final class ColumnArrayFetch implements ArrayFetchStrategyInterface
{
    /**
     * The desired column index
     *
     * @var int
     * @readonly
     */
    private int $column;

    /**
     * The desired column name
     *
     * @var string|null
     */
    private ?string $columnName;

    /**
     * @param int $column The column index (starts at 0)
     */
    public function __construct(int $column)
    {
        $this->column = $column;
    }

    /**
     * {@inheritdoc}
     */
    public function one(array $row)
    {
        if (!isset($this->columnName)) {
            $this->columnName = array_keys($row)[$this->column];
        }

        return $row[$this->columnName];
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $rows): array
    {
        $fetched = [];
        $columnName = $this->columnName ?? null;

        foreach ($rows as $row) {
            if (!$columnName) {
                $columnName = $this->columnName = array_keys($row)[$this->column];
            }

            $fetched[] = $row[$columnName];
        }

        return $fetched;
    }
}
