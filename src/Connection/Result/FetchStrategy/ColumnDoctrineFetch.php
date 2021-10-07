<?php

namespace Bdf\Prime\Connection\Result\FetchStrategy;

use Doctrine\DBAL\Result;

/**
 * Fetch a single column of each results
 *
 * @implements DoctrineFetchStrategyInterface<mixed>
 */
final class ColumnDoctrineFetch implements DoctrineFetchStrategyInterface
{
    /**
     * The column number (starts at 0)
     *
     * @var int
     * @readonly
     */
    private int $column;

    /**
     * @param int $column The column number (starts at 0)
     */
    public function __construct(int $column)
    {
        $this->column = $column;
    }

    /**
     * {@inheritdoc}
     */
    public function one(Result $result)
    {
        $column = $this->column;

        if ($column === 0) {
            return $result->fetchOne();
        }

        $row = $result->fetchNumeric();

        return $row ? $row[$column] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function all(Result $result): array
    {
        $column = $this->column;

        if ($column === 0) {
            return $result->fetchFirstColumn();
        }

        $rows = [];

        foreach ($result->fetchAllNumeric() as $row) {
            $rows[] = $row[$column];
        }

        return $rows;
    }
}
