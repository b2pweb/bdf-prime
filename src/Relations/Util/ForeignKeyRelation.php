<?php

namespace Bdf\Prime\Relations\Util;

use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Relations\AbstractRelation;
use Bdf\Prime\Relations\RelationInterface;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Adds foreign key accessor helpers for relations based on single foreign key
 *
 * @psalm-require-extends AbstractRelation
 *
 * @template L as object
 * @template R as object
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
     * @param Q $query
     * @param mixed $value
     * @return Q
     * @template Q as \Bdf\Prime\Query\Contract\Whereable&ReadCommandInterface
     * @psalm-suppress InvalidReturnType
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
     * @param L|L[] $entity
     *
     * @return mixed
     */
    protected function getLocalKeyValue($entity)
    {
        $mapper = $this->localRepository()->mapper();

        if (!is_array($entity)) {
            return $mapper->extractOne($entity, $this->localKey);
        }

        $keys = [];

        foreach ($entity as $e) {
            $keys[] = $mapper->extractOne($e, $this->localKey);
        }

        return $keys;
    }

    /**
     * Set the local key value on an entity
     *
     * @param L $entity
     * @param mixed  $id
     *
     * @return void
     */
    protected function setLocalKeyValue($entity, $id): void
    {
        $this->localRepository()->mapper()->hydrateOne($entity, $this->localKey, $id);
    }

    /**
     * Get the local key value from an entity
     *
     * @param R $entity
     *
     * @return mixed
     */
    protected function getDistantKeyValue($entity)
    {
        /** @psalm-suppress InvalidArgument */
        return $this->relationRepository()->mapper()->extractOne($entity, $this->distantKey);
    }

    /**
     * Get the distance key value on an entity
     *
     * @param R $entity
     * @param mixed $id
     *
     * @return void
     */
    protected function setDistantKeyValue($entity, $id): void
    {
        /** @psalm-suppress InvalidArgument */
        $this->relationRepository()->mapper()->hydrateOne($entity, $this->distantKey, $id);
    }

    /**
     * {@inheritdoc}
     *
     * @see RelationInterface::relationRepository()
     * @return RepositoryInterface<R>
     */
    abstract public function relationRepository(): RepositoryInterface;

    /**
     * {@inheritdoc}
     *
     * @see RelationInterface::localRepository()
     * @return RepositoryInterface<L>
     */
    abstract public function localRepository(): RepositoryInterface;
}
