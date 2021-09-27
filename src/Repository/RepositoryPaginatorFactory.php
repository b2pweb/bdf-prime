<?php

namespace Bdf\Prime\Repository;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\Pagination\PaginatorFactory;
use Bdf\Prime\Query\Pagination\Walker;
use Bdf\Prime\Query\Pagination\WalkStrategy\KeyWalkStrategy;
use Bdf\Prime\Query\Pagination\WalkStrategy\MapperPrimaryKey;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Paginator factory for a repository query
 *
 * @template E as object
 */
class RepositoryPaginatorFactory extends PaginatorFactory
{
    /**
     * @var RepositoryInterface<E>
     */
    private $repository;

    /**
     * RepositoryPaginatorFactory constructor.
     *
     * @param RepositoryInterface<E> $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;

        $this->addFactory(Walker::class, [$this, 'createWalker']);
    }

    /**
     * Create the walker instance for the given query
     *
     * @param ReadCommandInterface<ConnectionInterface, E> $query
     * @param int|null $limit
     * @param int|null $page
     *
     * @return Walker<E>
     */
    protected function createWalker(ReadCommandInterface $query, ?int $limit, ?int $page): Walker
    {
        $walker = new Walker($query, $limit, $page);

        if (
            !$this->repository->metadata()->isCompositePrimaryKey()
            && KeyWalkStrategy::supports($query, $page, $this->repository->metadata()->primary['attributes'][0])
        ) {
            $walker->setStrategy(new KeyWalkStrategy(new MapperPrimaryKey($this->repository->mapper())));
        }

        return $walker;
    }
}
