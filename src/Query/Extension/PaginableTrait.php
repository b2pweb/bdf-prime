<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Query\Pagination\PaginatorFactory;
use Bdf\Prime\Query\Pagination\PaginatorInterface;

/**
 * Trait for @see Paginable queries
 *
 * @psalm-require-implements Paginable
 */
trait PaginableTrait
{
    /**
     * @var PaginatorFactory|null
     */
    private $paginatorFactory;

    /**
     * @param PaginatorFactory $paginatorFactory
     */
    public function setPaginatorFactory(PaginatorFactory $paginatorFactory): void
    {
        $this->paginatorFactory = $paginatorFactory;
    }

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
     * {@inheritdoc}
     *
     * @see Paginable::paginate()
     */
    public function paginate(?int $maxRows = null, ?int $page = null, string $className = 'paginator'): PaginatorInterface
    {
        $factory = $this->paginatorFactory ?? PaginatorFactory::instance();

        return $factory->create($this, $className, $maxRows, $page);
    }

    /**
     * {@inheritdoc}
     *
     * @see Paginable::walk()
     */
    public function walk(?int $maxRows = null, ?int $page = null): PaginatorInterface
    {
        return $this->paginate($maxRows, $page, 'walker');
    }
}
