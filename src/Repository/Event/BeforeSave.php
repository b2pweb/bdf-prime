<?php

namespace Bdf\Prime\Repository\Event;

use Bdf\Prime\Repository\RepositoryInterface;

/**
 * @template E as object
 */
final class BeforeSave implements RepositoryEventInterface
{
    /**
     * @var E
     */
    public object $entity;

    /**
     * @var RepositoryInterface<E>
     */
    public RepositoryInterface $repository;
    public ?bool $isNew;

    /**
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     * @param bool|null $isNew
     */
    public function __construct(object $entity, RepositoryInterface $repository, ?bool $isNew)
    {
        $this->entity = $entity;
        $this->repository = $repository;
        $this->isNew = $isNew;
    }

    /**
     * {@inheritdoc}
     */
    public function legacyArgs(): array
    {
        return [$this->entity, $this->repository, $this->isNew];
    }
}
