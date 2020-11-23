<?php

namespace Bdf\Prime\Query\Contract;

use Bdf\Prime\Exception\PrimeException;

/**
 * Query with aggregate operations
 */
interface Aggregatable
{
    /**
     * Execute a COUNT function
     *
     * @param null|string $column
     *
     * @return int
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function count($column = null);

    /**
     * Execute a AVG function
     *
     * @param null|string $column
     *
     * @return float
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function avg($column = null);

    /**
     * Execute a MIN function
     *
     * @param null|string $column
     *
     * @return float
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function min($column = null);

    /**
     * Execute a MAX function
     *
     * @param null|string $column
     *
     * @return float
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function max($column = null);

    /**
     * Execute a SUM function
     *
     * @param null|string $column
     *
     * @return float
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function sum($column = null);

    /**
     * Execute a aggregate function
     *
     * @param string $function
     * @param null|string $column
     *
     * @return string
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function aggregate($function, $column = null);
}