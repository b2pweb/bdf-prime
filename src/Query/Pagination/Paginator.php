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
    public const DEFAULT_PAGE  = 1;
    public const DEFAULT_LIMIT = 20;

    /**
     * Create a query paginator
     *
     * @param ReadCommandInterface<ConnectionInterface, R>&Limitable&Orderable&Paginable $query
     * @param int|null $maxRows
     * @param int|null $page
     *
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function __construct(ReadCommandInterface $query, ?int $maxRows = null, ?int $page = null)
    {
        $this->query = $query;
        $this->maxRows = $maxRows ?: self::DEFAULT_LIMIT;
        $this->page = $page ?: self::DEFAULT_PAGE;

        $this->loadCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function loadCollection(): void
    {
        parent::loadCollection();

        if (!($this->collection instanceof CollectionInterface)) {
            /** @psalm-suppress NoValue */
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
