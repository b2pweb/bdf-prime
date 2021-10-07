<?php

namespace Bdf\Prime\Sharding;

use ArrayIterator;
use Doctrine\DBAL\Driver\Result as DriverResult;
use Doctrine\DBAL\Result;
use IteratorAggregate;

/**
 * Aggregation of query results
 * This class handle results of multiple shards query
 */
final class MultiResult implements IteratorAggregate, DriverResult
{
    /**
     * @var list<Result>
     */
    private array $results;

    /**
     * @var int
     */
    private int $current = 0;

    /**
     * @param list<Result> $results The connection query Result instances
     */
    public function __construct(array $results = [])
    {
        $this->results = $results;
    }

    /**
     * Add a query Result into the sharding result
     */
    public function add(Result $result): void
    {
        $this->results[] = $result;
    }

    /**
     * {@inheritdoc}
     */
    public function free(): void
    {
        foreach ($this->results as $result) {
            $result->free();
        }

        unset($this->results);
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount(): int
    {
        if (!isset($this->results[0])) {
            return 0;
        }

        return $this->results[0]->columnCount();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->fetchAllAssociative());
    }

    /**
     * {@inheritdoc}
     */
    public function fetchNumeric()
    {
        return $this->fetch(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAssociative()
    {
        return $this->fetch(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne()
    {
        return $this->fetch(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllNumeric(): array
    {
        return $this->fetchAll(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllAssociative(): array
    {
        return $this->fetchAll(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchFirstColumn(): array
    {
        return $this->fetchAll(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount(): int
    {
        $count = 0;

        foreach ($this->results as $statement) {
            $count += $statement->rowCount();
        }

        return $count;
    }

    /**
     * @param string $method
     *
     * @return false|mixed False if there is no more results, or the current row value
     */
    private function fetch(string $method)
    {
        for (;; ++$this->current) {
            // Stop the fetch if there is no statement
            if (!isset($this->results[$this->current])) {
                return false;
            }

            $result = $this->results[$this->current]->$method();

            if ($result) {
                return $result;
            }
        }
    }

    /**
     * @param string $method
     * @return list<mixed>
     */
    private function fetchAll(string $method)
    {
        $result = [];

        foreach ($this->results as $statement) {
            $result = array_merge($result, $statement->$method());
        }

        return $result;
    }
}
