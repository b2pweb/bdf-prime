<?php

namespace Bdf\Prime\Repository\Event;

use Bdf\Prime\Repository\RepositoryInterface;

/**
 * @template E as object
 */
final class AfterSave implements RepositoryEventInterface
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
    public ?bool $isNew;

    /**
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     * @param int $affectedRows
     * @param bool|null $isNew
     */
    public function __construct(object $entity, RepositoryInterface $repository, int $affectedRows, ?bool $isNew)
    {
        $this->entity = $entity;
        $this->repository = $repository;
        $this->affectedRows = $affectedRows;
        $this->isNew = $isNew;
    }

    /**
     * {@inheritdoc}
     */
    public function legacyArgs(): array
    {
        return [$this->entity, $this->repository, $this->affectedRows, $this->isNew];
    }
}
