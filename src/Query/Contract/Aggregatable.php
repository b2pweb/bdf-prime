<?php

namespace Bdf\Prime\Query\Contract;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Expression\Aggregate;

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
     *
     * @see Aggregate::count() For generate a count query
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
     *
     * @see Aggregate::avg() For generate a avg query
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
     *
     * @see Aggregate::min() For generate a min query
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
     *
     * @see Aggregate::max() For generate a max query
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
     *
     * @see Aggregate::sum() For generate a sum query
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
