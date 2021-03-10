<?php

namespace Bdf\Prime\Repository;

use Bdf\Prime\Query\Pagination\PaginatorFactory;
use Bdf\Prime\Query\Pagination\Walker;
use Bdf\Prime\Query\Pagination\WalkStrategy\KeyWalkStrategy;
use Bdf\Prime\Query\Pagination\WalkStrategy\MapperPrimaryKey;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Paginator factory for a repository query
 */
class RepositoryPaginatorFactory extends PaginatorFactory
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * RepositoryPaginatorFactory constructor.
     *
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;

        $this->addFactory(Walker::class, [$this, 'createWalker']);
    }

    /**
     * Create the walker instance for the given query
     *
     * @param ReadCommandInterface $query
     * @param int|null $limit
     * @param int|null $page
     *
     * @return Walker
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
