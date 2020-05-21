<?php

namespace Bdf\Prime\Query\Contract;

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
     */
    public function count($column = null);

    /**
     * Execute a AVG function
     *
     * @param null|string $column
     *
     * @return float
     */
    public function avg($column = null);

    /**
     * Execute a MIN function
     *
     * @param null|string $column
     *
     * @return float
     */
    public function min($column = null);

    /**
     * Execute a MAX function
     *
     * @param null|string $column
     *
     * @return float
     */
    public function max($column = null);

    /**
     * Execute a SUM function
     *
     * @param null|string $column
     *
     * @return float
     */
    public function sum($column = null);

    /**
     * Execute a aggregate function
     *
     * @param string $function
     * @param null|string $column
     *
     * @return string
     */
    public function aggregate($function, $column = null);
}