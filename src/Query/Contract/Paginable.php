<?php

namespace Bdf\Prime\Query\Contract;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Pagination\PaginatorFactory;

/**
 * Interface for query which are iterable and walkable
 * Most of Paginable queries should also be @see Limitable
 */
interface Paginable extends \IteratorAggregate
{
    /**
     * Get entity collection iterator
     * Allowed user to create a cursor on a collection of big data
     *
     * @param int|null    $maxRows
     * @param int|null    $page
     * @param string      $className  Classname of the paginator. Default the registered 'paginator'
     *
     * @return \Bdf\Prime\Query\Pagination\PaginatorInterface
     * @throws PrimeException
     */
    #[ReadOperation]
    public function paginate($maxRows = null, $page = null, $className = 'paginator');

    /**
     * Create a cursor on a big collection
     *
     * @param int|null    $maxRows
     * @param int|null    $page
     *
     * @return \Bdf\Prime\Query\Pagination\PaginatorInterface
     * @throws PrimeException
     */
    #[ReadOperation]
    public function walk($maxRows = null, $page = null);

    /**
     * Get the count of the query for pagination
     * Column could be an array if DISTINCT is on
     *
     * @param array|string $column
     *
     * @return int The whole number of rows (ignoring limit and offset)
     * @throws PrimeException
     */
    #[ReadOperation]
    public function paginationCount($column = null);

    /**
     * Define the paginator factory to use on the query
     *
     * @param PaginatorFactory $paginatorFactory
     */
    public function setPaginatorFactory(PaginatorFactory $paginatorFactory): void;
}
