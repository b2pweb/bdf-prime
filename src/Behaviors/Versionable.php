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
 * @package Bdf\Prime\Behaviors
 */
class Versionable extends Behavior
{
    const COLUMN_NAME = 'version';

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
     * @param object                 $entity
     * @param RepositoryInterface    $repository
     */
    public function beforeInsert($entity, $repository)
    {
        $this->incrementVersion($entity, $repository);
    }

    /**
     * After insert
     *
     * we historicize entity
     *
     * @param object                 $entity
     * @param RepositoryInterface $repository
     * @param integer $count
     */
    public function afterInsert($entity, $repository, $count)
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
     * @param object                 $entity
     * @param RepositoryInterface    $repository
     * @param null|\ArrayObject      $attributes
     */
    public function beforeUpdate($entity, $repository, $attributes)
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
     * @param object                 $entity
     * @param RepositoryInterface    $repository
     * @param integer $count
     */
    public function afterUpdate($entity, $repository, $count)
    {
        if ($count != 0) {
            $this->insertVersion($entity, $repository);
        }
    }

    /**
     * Remove entity versions
     *
     * @param object                 $entity
     * @param RepositoryInterface    $repository
     */
    public function deleteAllVersions($entity, $repository)
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
     * @param object                 $entity
     * @param RepositoryInterface    $repository
     */
    protected function incrementVersion($entity, $repository)
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
     * @param object                 $entity
     * @param RepositoryInterface    $repository
     */
    protected function insertVersion($entity, $repository)
    {
        $repository->repository($this->versionClass)->insert($entity);
    }
}
