<?php

namespace Bdf\Prime\Query\Pagination\WalkStrategy;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * The strategy use for walk through a query results
 */
interface WalkStrategyInterface
{
    /**
     * Configure the query and initialize the cursor
     * This method should not perform any request
     *
     * @param ReadCommandInterface $query The base query to iterate on
     * @param int $chunkSize The chunk size
     * @param int $startPage The start page
     *
     * @return WalkCursor
     */
    public function initialize(ReadCommandInterface $query, int $chunkSize, int $startPage): WalkCursor;

    /**
     * Load next entities and move cursor
     *
     * @param WalkCursor $cursor Current cursor
     *
     * @return WalkCursor The next cursor (should be a new instance)
     *
     * @throws PrimeException
     */
    #[ReadOperation]
    public function next(WalkCursor $cursor): WalkCursor;
}
