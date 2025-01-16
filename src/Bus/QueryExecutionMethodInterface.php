<?php

namespace Bdf\Prime\Bus;

use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Define the execution method of the query
 *
 * @see PrimeQuery::$method To define the method on the query DTO
 */
interface QueryExecutionMethodInterface
{
    /**
     * Execute the query and get the result
     *
     * @param ReadCommandInterface $query The query to execute
     * @param array{limit?: int, offset?: int, page?: int} $options Extra option to configure the execution
     *
     * @return mixed The result of the query
     */
    public function execute(ReadCommandInterface $query, array $options = []): mixed;
}
