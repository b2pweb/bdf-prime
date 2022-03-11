<?php

namespace Bdf\Prime\Query\Contract;

use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Exception\PrimeException;

/**
 * Interface for queries which can be executed
 */
interface SelfExecutable
{
    /**
     * Execute the query and get the connection's result
     *
     * @return ResultSetInterface<array<string, mixed>>
     * @throws PrimeException When execute fail
     */
    public function execute(): ResultSetInterface;
}
