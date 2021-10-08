<?php

namespace Bdf\Prime\Connection\Result\FetchStrategy;

use Doctrine\DBAL\Result;

/**
 * Fetch algorithm for a doctrine Result
 *
 * @template T
 */
interface DoctrineFetchStrategyInterface
{
    /**
     * Fetch only one row
     *
     * Note: this method will move the cursor to the next row
     *
     * @param Result $result
     *
     * @return T|false The fetched value, or false if the end of the result is reach
     */
    public function one(Result $result);

    /**
     * Fetch all rows of the result
     *
     * @param Result $result
     *
     * @return list<T>
     */
    public function all(Result $result): array;
}
