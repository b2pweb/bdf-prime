<?php

namespace Bdf\Prime\Repository\Event;

use Bdf\Prime\Repository\RepositoryInterface;

/**
 * @template E as object
 */
final class BeforeDelete implements RepositoryEventInterface
{
    /**
     * @var E
     */
    public object $entity;

    /**
     * @var RepositoryInterface<E>
     */
    public RepositoryInterface $repository;

    /**
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     */
    public function __construct(object $entity, RepositoryInterface $repository)
    {
        $this->entity = $entity;
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function legacyArgs(): array
    {
        return [$this->entity, $this->repository];
    }
}
