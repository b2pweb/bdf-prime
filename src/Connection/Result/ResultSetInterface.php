<?php

namespace Bdf\Prime\Connection\Result;

use Countable;
use Iterator;

/**
 * Store result of the query execution
 * The result set should not be reset
 *
 * <code>
 * $connection
 *     ->execute($query)
 *     ->asColumn(2)
 *     ->all()
 * ;
 * </code>
 *
 * @template T
 * @extends Iterator<int, T>
 */
interface ResultSetInterface extends Iterator, Countable
{
    /**
     * Fetch the rows as associative array
     * This is the default fetch mode
     * No options available
     *
     * @deprecated Use asAssociative() instead
     */
    const FETCH_ASSOC = 'assoc';

    /**
     * Fetch the rows as a numeric array
     * No options available
     *
     * @deprecated Use asList() instead
     */
    const FETCH_NUM = 'num';

    /**
     * Fetch only one columns on each rows
     * Option : integer column number to fetch. Starts at 0 (zero)
     *
     * @deprecated Use asColumn() instead
     */
    const FETCH_COLUMN = 'column';

    /**
     * Fetch rows into a simple object (stdClass)
     * No options available
     *
     * @deprecated Use asObject() instead
     */
    const FETCH_OBJECT = 'object';

    /**
     * Fetch rows into a new class
     * Option : string The class name
     *
     * @deprecated Use asClass() instead
     */
    const FETCH_CLASS = 'class';


    /**
     * @param string $mode The fetch mode. Should be one of the ResultSetInterface::FETCH_* constant
     * @param mixed $options
     *
     * @return $this
     *
     * @deprecated Use dedicated method instead
     */
    public function fetchMode($mode, $options = null);

    /**
     * The result will be fetched as associative array
     *
     * Note: this is the default behavior of the connection
     *
     * @return static<array<string, mixed>>
     */
    public function asAssociative(): self;

    /**
     * The value will be fetched as numeric array
     *
     * @return static<list<mixed>>
     */
    public function asList(): self;

    /**
     * The result will be fetched as simple object (stdClass)
     *
     * @return static<\stdClass>
     */
    public function asObject(): self;

    /**
     * The result will be fetched as an instance of a class
     *
     * Note: the entity will be instantiated first, and then properties will be filled
     *
     * @param class-string<E> $className The result class name
     *
     * @return static<E>
     *
     * @template E
     */
    public function asClass(string $className): self;

    /**
     * The result fetch only one column
     *
     * @param int $column The column number
     *
     * @return static<mixed>
     */
    public function asColumn(int $column = 0): self;

    /**
     * Get all results as an array
     *
     * @return list<T>
     */
    public function all();

    /**
     * {@inheritdoc}
     *
     * The rewind operation is not guaranteed to works, and may be a no-op on some connections
     */
    public function rewind();

    /**
     * {@inheritdoc}
     *
     * Get the number of affected rows by an update operation
     * Some drivers may return the number of rows for a select query, but it's not guaranteed
     */
    public function count();

    /**
     * Check if the result is for a read operation
     *
     * @return bool true if it's a read operation
     */
    public function isRead(): bool;

    /**
     * Check if the result is for a write operation
     *
     * @return bool true if it's a write operation
     */
    public function isWrite(): bool;

    /**
     * Does a write operation has been performed, and affected rows ?
     *
     * @return bool true if rows has been affected
     */
    public function hasWrite(): bool;
}