<?php

namespace Bdf\Prime\Query\Pagination;

use Bdf\Prime\Collection\ArrayCollection;
use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\Contract\Orderable;
use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;
use IteratorAggregate;

/**
 * Query Paginator
 * 
 * @author  Seb
 * @package Bdf\Prime\Query\Pagination
 *
 * @template R as array|object
 *
 * @implements PaginatorInterface<R>
 * @implements IteratorAggregate<array-key, R>
 * @extends AbstractPaginator<R>
 *
 * @property CollectionInterface<R> $collection protected
 */
class Paginator extends AbstractPaginator implements IteratorAggregate, PaginatorInterface
{
    const DEFAULT_PAGE  = 1;
    const DEFAULT_LIMIT = 20;

    /**
     * Create a query paginator
     *
     * @param ReadCommandInterface<ConnectionInterface, R>&Limitable&Orderable&Paginable $query
     * @param int $maxRows
     * @param int $page
     *
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function __construct(ReadCommandInterface $query, $maxRows = null, $page = null)
    {
        $this->query = $query;
        $this->maxRows = $maxRows ?: self::DEFAULT_LIMIT;
        $this->page = $page ?: self::DEFAULT_PAGE;
        
        $this->loadCollection();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function loadCollection()
    {
        parent::loadCollection();
        
        if (!($this->collection instanceof CollectionInterface)) {
            $this->collection = new ArrayCollection($this->collection);
        }
    }
    
    /**
     * SPL - IteratorAggregate
     *
     * {@inheritdoc}
     */
    public function getIterator(): CollectionInterface
    {
        return $this->collection;
    }
}
