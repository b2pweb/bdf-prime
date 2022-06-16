<?php

namespace Bdf\Prime\Behaviors;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
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
class Versionable extends Behavior
{
    public const COLUMN_NAME = 'version';

    /**
     * The version repository className
     *
     * @var string
     */
    protected $versionClass;

    /**
     * Allow version deletion
     *
     * @var boolean
     */
    protected $allowDeletion;

    /**
     * Version table name
     *
     * @var string
     */
    protected $tableName;

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
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     *
     * @return void
     */
    public function beforeInsert($entity, RepositoryInterface $repository): void
    {
        $this->incrementVersion($entity, $repository);
    }

    /**
     * After insert
     *
     * we historicize entity
     *
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     * @param int $count
     *
     * @return void
     */
    public function afterInsert($entity, RepositoryInterface $repository, int $count): void
    {
        if ($count != 0) {
            $this->insertVersion($entity, $repository);
        }
    }

    /**
     * Before update
     *
     * we increment version number on entity
     *
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     * @param null|\ArrayObject $attributes
     *
     * @return void
     */
    public function beforeUpdate($entity, RepositoryInterface $repository, $attributes): void
    {
        if ($attributes !== null) {
            $attributes[] = self::COLUMN_NAME;
        }

        $this->incrementVersion($entity, $repository);
    }

    /**
     * After update
     *
     * we historicize entity
     *
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     * @param int $count
     *
     * @return void
     */
    public function afterUpdate($entity, RepositoryInterface $repository, int $count): void
    {
        if ($count != 0) {
            $this->insertVersion($entity, $repository);
        }
    }

    /**
     * Remove entity versions
     *
     * @param E $entity
     * @param RepositoryInterface<E> $repository
     *
     * @return void
     */
    public function deleteAllVersions($entity, RepositoryInterface $repository): void
    {
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
        $notifier->inserting([$this, 'beforeInsert']);
        $notifier->inserted([$this, 'afterInsert']);

        $notifier->updating([$this, 'beforeUpdate']);
        $notifier->updated([$this, 'afterUpdate']);

        if ($this->allowDeletion) {
            $notifier->deleted([$this, 'deleteAllVersions']);
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
