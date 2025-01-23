<?php

namespace Bdf\Prime\Bus\Query;

use Bdf\Prime\Bus\Query\ReturnType\ReturnTypeInterface;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\ServiceLocator;

/**
 * Base type for query handler which generate a single prime query to execute
 *
 * @template T as object
 */
interface QueryGeneratorPrimeQueryHandlerInterface
{
    /**
     * Execute the query and return the result
     *
     * @param ServiceLocator $prime The prime service locator
     * @param T $query The query DTO
     * @param string|ReturnTypeInterface|null $returnType The request return type. It can be used to select the source of the query
     *
     * @return mixed The result of the query
     */
    public function __invoke(ServiceLocator $prime, object $query, string|ReturnTypeInterface|null $returnType = null): mixed;

    /**
     * Generate the query that will be executed by the handler
     *
     * @param ServiceLocator $prime The prime service locator
     * @param T $query The query DTO
     * @param string|ReturnTypeInterface|null $returnType The request return type. It can be used to select the source of the query
     *
     * @return ReadCommandInterface The generated query
     */
    public function generateQuery(ServiceLocator $prime, object $query, string|ReturnTypeInterface|null $returnType = null): ReadCommandInterface;
}
