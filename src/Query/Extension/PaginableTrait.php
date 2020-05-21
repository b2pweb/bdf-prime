<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Query\Pagination\PaginatorFactory;

/**
 * Trait for @see Paginable queries
 */
trait PaginableTrait
{
    /**
     * SPL - IteratorAggregate
     *
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->walk();
    }

    /**
     * @see Paginable::paginate()
     */
    public function paginate($maxRows = null, $page = null, $className = 'paginator')
    {
        return PaginatorFactory::create($this, $className, $maxRows, $page);
    }

    /**
     * @see Paginable::walk()
     */
    public function walk($maxRows = null, $page = null)
    {
        return $this->paginate($maxRows, $page, 'walker');
    }
}
