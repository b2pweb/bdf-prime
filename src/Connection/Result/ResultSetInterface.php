<?php


namespace Bdf\Prime\Connection\Result;

/**
 * Store result of the query execution
 * The result set should not be reset
 *
 * <code>
 * $connection
 *     ->execute($query)
 *     ->fetchMode(ResultSetInterface::FETCH_COLUMN, 2)
 *     ->all()
 * ;
 * </code>
 */
interface ResultSetInterface extends \Iterator, \Countable
{
    /**
     * Fetch the rows as associative array
     * This is the default fetch mode
     * No options available
     */
    const FETCH_ASSOC = 'assoc';

    /**
     * Fetch the rows as a numeric array
     * No options available
     */
    const FETCH_NUM = 'num';

    /**
     * Fetch only one columns on each rows
     * Option : integer column number to fetch. Starts at 0 (zero)
     */
    const FETCH_COLUMN = 'column';

    /**
     * Fetch rows into a simple object (stdClass)
     * No options available
     */
    const FETCH_OBJECT = 'object';

    /**
     * Fetch rows into a new class
     * Option : string The class name
     */
    const FETCH_CLASS = 'class';


    /**
     * @param string $mode The fetch mode. Should be one of the ResultSetInterface::FETCH_* constant
     * @param null $options
     *
     * @return $this
     */
    public function fetchMode($mode, $options = null);

    /**
     * Get all results as an array
     *
     * @return array
     */
    public function all();

    /**
     * {@inheritdoc}
     *
     * The rewind operation is not guaranted to works, and may be a no-op on some connections
     */
    public function rewind();

    /**
     * {@inheritdoc}
     *
     * Get the number of affected rows by an update operation
     * Some drivers may return the number of rows for a select query, but it's not guaranted
     */
    public function count();
}
