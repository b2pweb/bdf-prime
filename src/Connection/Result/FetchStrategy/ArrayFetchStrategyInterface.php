<?php

namespace Bdf\Prime\Connection\Result\FetchStrategy;

/**
 * Strategy for transform simple array to desired type
 *
 * @template T
 */
interface ArrayFetchStrategyInterface
{
    /**
     * Fetch only one row
     *
     * @param array<string, mixed> $row
     *
     * @return T The fetched value
     */
    public function one(array $row);

    /**
     * Fetch all rows
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return list<T>
     */
    public function all(array $rows): array;
}
