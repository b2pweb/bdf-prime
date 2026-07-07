<?php

namespace Bdf\Prime\Repository\Event;

use Bdf\Prime\Repository\RepositoryInterface;

/**
 * @template E as object
 */
final class AfterUpdate implements RepositoryEventInterface
{
    /**
     * @var E
     */
    public object $entity;

    /**
     * @var RepositoryInterface<E>
     */
    public RepositoryInterface $repository;
    public int $affectedRows;

    /**
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     * @param int $affectedRows
     */
    public function __construct(object $entity, RepositoryInterface $repository, int $affectedRows)
    {
        $this->entity = $entity;
        $this->repository = $repository;
        $this->affectedRows = $affectedRows;
    }
}
