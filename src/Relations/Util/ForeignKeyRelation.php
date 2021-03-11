<?php

namespace Bdf\Prime\Relations\Util;

use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Relations\AbstractRelation;
use Bdf\Prime\Relations\RelationInterface;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Adds foreign key accessor helpers for relations based on single foreign key
 */
trait ForeignKeyRelation
{
    /**
     * The local property for the relation
     *
     * @var string
     */
    protected $localKey;

    /**
     * The distant key
     *
     * @var string
     */
    protected $distantKey;


    /**
     * {@inheritdoc}
     *
     * @see AbstractRelation::applyWhereKeys()
     */
    protected function applyWhereKeys(ReadCommandInterface $query, $value): ReadCommandInterface
    {
        return $query->where($this->distantKey, $value);
    }

    /**
     * Get the local key value from an entity
     * If an array is given, get array of key value
     *
     * @param object|object[] $entity
     *
     * @return mixed
     */
    protected function getLocalKeyValue($entity)
    {
        if (!is_array($entity)) {
            return $this->localRepository()->extractOne($entity, $this->localKey);
        }

        $keys = [];

        foreach ($entity as $e) {
            $keys[] = $this->localRepository()->extractOne($e, $this->localKey);
        }

        return $keys;
    }

    /**
     * Set the local key value on an entity
     *
     * @param object $entity
     * @param mixed  $id
     *
     * @return mixed
     */
    protected function setLocalKeyValue($entity, $id)
    {
        return $this->localRepository()->hydrateOne($entity, $this->localKey, $id);
    }

    /**
     * Get the local key value from an entity
     *
     * @param object $entity
     *
     * @return mixed
     */
    protected function getDistantKeyValue($entity)
    {
        return $this->relationRepository()->extractOne($entity, $this->distantKey);
    }

    /**
     * Get the distance key value on an entity
     *
     * @param object $entity
     * @param mixed  $id
     *
     * @return mixed
     */
    protected function setDistantKeyValue($entity, $id)
    {
        return $this->relationRepository()->hydrateOne($entity, $this->distantKey, $id);
    }

    /**
     * {@inheritdoc}
     *
     * @see RelationInterface::relationRepository()
     * @return RepositoryInterface
     */
    abstract public function relationRepository(): RepositoryInterface;

    /**
     * {@inheritdoc}
     *
     * @see RelationInterface::localRepository()
     * @return RepositoryInterface
     */
    abstract public function localRepository(): RepositoryInterface;
}
