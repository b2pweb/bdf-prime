<?php

namespace Bdf\Prime\Repository\Event;

use ArrayObject;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * @template E as object
 */
final class BeforeUpdate implements RepositoryEventInterface
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
     * @var ArrayObject<int, string>|null
     */
    public ?ArrayObject $attributes;

    /**
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     * @param ArrayObject<int, string>|null $attributes
     */
    public function __construct(object $entity, RepositoryInterface $repository, ?ArrayObject $attributes)
    {
        $this->entity = $entity;
        $this->repository = $repository;
        $this->attributes = $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function legacyArgs(): array
    {
        return [$this->entity, $this->repository, $this->attributes];
    }
}
