<?php

namespace Bdf\Prime\Query\Contract;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Pagination\PaginatorFactory;
use Bdf\Prime\Query\Pagination\PaginatorInterface;
use IteratorAggregate;

/**
 * Interface for query which are iterable and walkable
 * Most of Paginable queries should also be @see Limitable
 *
 * @template R as array|object
 * @extends IteratorAggregate<array-key, R>
 */
interface Paginable extends IteratorAggregate
{
    /**
     * Get entity collection iterator
     * Allowed user to create a cursor on a collection of big data
     *
     * @param int|null $maxRows
     * @param int|null $page
     * @param string $className  Classname of the paginator. Default the registered 'paginator'
     *
     * @return PaginatorInterface<R>
     * @throws PrimeException
     */
    #[ReadOperation]
    public function paginate(?int $maxRows = null, ?int $page = null, string $className = 'paginator'): PaginatorInterface;

    /**
     * Create a cursor on a big collection
     *
     * @param int|null $maxRows
     * @param int|null $page
     *
     * @return PaginatorInterface<R>
     * @throws PrimeException
     */
    #[ReadOperation]
    public function walk(?int $maxRows = null, ?int $page = null): PaginatorInterface;

    /**
     * Get the count of the query for pagination
     *
     * @param string|null $column
     *
     * @return int The whole number of rows (ignoring limit and offset)
     * @throws PrimeException
     */
    #[ReadOperation]
    public function paginationCount(?string $column = null): int;

    /**
     * Define the paginator factory to use on the query
     *
     * @param PaginatorFactory $paginatorFactory
     */
    public function setPaginatorFactory(PaginatorFactory $paginatorFactory): void;
}
