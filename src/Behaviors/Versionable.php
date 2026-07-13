<?php

namespace Bdf\Prime\Behaviors;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Repository\Event\AfterDelete;
use Bdf\Prime\Repository\Event\AfterInsert;
use Bdf\Prime\Repository\Event\AfterUpdate;
use Bdf\Prime\Repository\Event\BeforeInsert;
use Bdf\Prime\Repository\Event\BeforeUpdate;
use Bdf\Prime\Repository\RepositoryEventsSubscriberInterface;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Versionable
 *
 * The versionable behavior allows you to keep an history of your model objects.
 *
 * @template E as object
 * @extends Behavior<E>
 */
final class Versionable extends Behavior
{
    public const COLUMN_NAME = 'version';

    /**
     * The version repository className
     */
    private string $versionClass;

    /**
     * Allow version deletion
     */
    private bool $allowDeletion;

    /**
     * Versionable constructor.
     *
     * @param string $versionClass
     * @param bool   $allowDeletion
     */
    public function __construct($versionClass, $allowDeletion = false)
    {
        $this->versionClass = $versionClass;
        $this->allowDeletion = $allowDeletion;
    }

    /**
     * {@inheritdoc}
     */
    public function changeSchema(FieldBuilder $builder): void
    {
        $builder->integer(self::COLUMN_NAME, 0);
    }

    /**
     * Before insert
     *
     * we increment version number on entity
     *
     * @param BeforeInsert<E> $event
     *
     * @return void
     */
    public function beforeInsert(BeforeInsert $event): void
    {
        $this->incrementVersion($event->entity, $event->repository);
    }

    /**
     * After insert
     *
     * we historicize entity
     *
     * @param AfterInsert<E> $event
     *
     * @return void
     */
    public function afterInsert(AfterInsert $event): void
    {
        if ($event->affectedRows != 0) {
            $this->insertVersion($event->entity, $event->repository);
        }
    }

    /**
     * Before update
     *
     * we increment version number on entity
     *
     * @param BeforeUpdate<E> $event
     *
     * @return void
     */
    public function beforeUpdate(BeforeUpdate $event): void
    {
        if ($event->attributes !== null) {
            /** @psalm-suppress NullArgument */
            $event->attributes[] = self::COLUMN_NAME;
        }

        $this->incrementVersion($event->entity, $event->repository);
    }

    /**
     * After update
     *
     * we historicize entity
     *
     * @param AfterUpdate<E> $event
     *
     * @return void
     */
    public function afterUpdate(AfterUpdate $event): void
    {
        if ($event->affectedRows != 0) {
            $this->insertVersion($event->entity, $event->repository);
        }
    }

    /**
     * Remove entity versions
     *
     * @param AfterDelete<E> $event
     *
     * @return void
     */
    public function deleteAllVersions(AfterDelete $event): void
    {
        $entity = $event->entity;
        $repository = $event->repository;
        $queries = $repository->repository($this->versionClass)->queries();
        $criteria = $repository->mapper()->primaryCriteria($entity);

        if ($query = $queries->keyValue($criteria)) {
            $query->delete();
        } else {
            $queries->builder()->where($criteria)->delete();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(RepositoryEventsSubscriberInterface $notifier): void
    {
        $notifier->inserting($this->beforeInsert(...));
        $notifier->inserted($this->afterInsert(...));

        $notifier->updating($this->beforeUpdate(...));
        $notifier->updated($this->afterUpdate(...));

        if ($this->allowDeletion) {
            $notifier->deleted($this->deleteAllVersions(...));
        }
    }

    /**
     * Increment version number on entity
     *
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     *
     * @return void
     */
    protected function incrementVersion($entity, RepositoryInterface $repository): void
    {
        $mapper = $repository->mapper();

        $mapper->hydrateOne(
            $entity,
            self::COLUMN_NAME,
            $mapper->extractOne($entity, self::COLUMN_NAME) + 1
        );
    }

    /**
     * Historicize entity
     *
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     *
     * @return void
     */
    protected function insertVersion($entity, RepositoryInterface $repository): void
    {
        $repository->repository($this->versionClass)->insert($entity);
    }
}
