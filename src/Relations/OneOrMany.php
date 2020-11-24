<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * OneOrMany
 * 
 * @package Bdf\Prime\Relations
 * 
 * @todo possibilité de désactiver les constraints globales
 */
abstract class OneOrMany extends Relation
{
    /**
     * {@inheritdoc}
     */
    public function relationRepository()
    {
        return $this->distant;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyConstraints($query, $constraints = [], $context = null)
    {
        parent::applyConstraints($query, $constraints, $context);

        // Si le dépot distant possède la foreign key, on estime qu'il possède le discriminator
        // On applique donc la contrainte de relation sur le discriminitor
        if ($this->isPolymorphic() && $this->isForeignKeyBarrier($this->distant->entityClass())) {
            $query->where(
                $this->applyContext($context, [$this->discriminator => $this->discriminatorValue])
            );
        }

        return $query;
    }
    
    /**
     * {@inheritdoc}
     */
    public function join($query, $alias = null)
    {
        if ($alias === null) {
            $alias = $this->attributeAim;
        }

        $query->joinEntity($this->distant->entityName(), $this->distantKey, $this->getLocalAlias($query).$this->localKey, $alias);

        // apply relation constraints
        $this->applyConstraints($query, [], '$'.$alias);
    }

    /**
     * {@inheritdoc}
     */
    public function joinRepositories(EntityJoinable $query, $alias = null, $discriminatorValue = null)
    {
        return [
            $alias => $this->relationRepository()
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    protected function relations($keys, $with, $constraints, $without)
    {
        return $this->relationQuery($keys, $constraints)
            ->with($with)
            ->without($without)
            ->all();
    }
    
    /**
     * Set the relation in a collection of entities
     * 
     * @param array $collection
     * @param array $relations
     */
    protected function match($collection, $relations)
    {
        foreach ($relations as $key => $distant) {
            foreach ($collection[$key] as $local) {
                $this->setRelation($local, $distant);
            }
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function link($owner)
    {
        return $this->query($this->getLocalKeyValue($owner));
    }
    
    /**
     * {@inheritdoc}
     */
    public function associate($owner, $entity)
    {
        if (!$this->isForeignKeyBarrier($owner)) {
            throw new \InvalidArgumentException('The local entity is not the foreign key barrier.');
        }

        if ($this->isPolymorphic()) {
            $this->discriminatorValue = $this->discriminator(get_class($entity));
        }

        $this->setForeignKeyValue($owner, $this->getDistantKeyValue($entity));
        $this->setRelation($owner, $entity);

        return $owner;
    }
    
    /**
     * {@inheritdoc}
     */
    public function dissociate($owner)
    {
        if (!$this->isForeignKeyBarrier($owner)) {
            throw new \InvalidArgumentException('The local entity is not the foreign key barrier.');
        }

        if ($this->isPolymorphic()) {
            $this->discriminatorValue = null;
        }
        
        // TODO Dont update key if it is embedded in the relation object
        $this->setForeignKeyValue($owner, null);
        $this->setRelation($owner, null);

        return $owner;
    }
    
    /**
     * {@inheritdoc}
     */
    public function create($owner, array $data = [])
    {
        if ($this->isForeignKeyBarrier($owner)) {
            throw new \InvalidArgumentException('The local entity is not the primary key barrier.');
        }

        $entity = $this->distant->entity($data);

        $this->setForeignKeyValue($entity, $this->getLocalKeyValue($owner));

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function add($owner, $entity)
    {
        if ($this->isForeignKeyBarrier($owner)) {
            throw new \InvalidArgumentException('The local entity is not the primary key barrier.');
        }

        $this->setForeignKeyValue($entity, $this->getLocalKeyValue($owner));

        return $this->distant->save($entity);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function saveAll($owner, array $relations = [])
    {
        $entities = $this->getRelation($owner);

        if (empty($entities)) {
            return 0;
        }
        
        $id = $this->getLocalKeyValue($owner);
        
        //Detach all relations
        if ($this->saveStrategy === self::SAVE_STRATEGY_REPLACE) {
            $this->query($id)->delete();
        }

        if (!is_array($entities)) {
            $entities = [$entities];
        }
        
        // Save new relations
        $nb = 0;
        foreach ($entities as $entity) {
            $this->setForeignKeyValue($entity, $id);
            $nb += $this->distant->saveAll($entity, $relations);
        }

        return $nb;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function deleteAll($owner, array $relations = [])
    {
        $entities = $this->getRelation($owner);

        if (empty($entities)) {
            return 0;
        }
        
        if (!is_array($entities)) {
            $entities = [$entities];
        }
        
        $nb = 0;
        foreach ($entities as $entity) {
            $nb += $this->distant->deleteAll($entity, $relations);
        }

        return $nb;
    }

    /**
     * Get the repository that owns the foreign key and the key name
     *
     * @return array
     */
    abstract protected function getForeignInfos();

    /**
     * Get the query used to load relations
     *
     * @param array $keys The owner keys
     * @param array $constraints Constraints to apply on the query
     *
     * @return ReadCommandInterface
     */
    abstract protected function relationQuery($keys, $constraints);

    /**
     * Check if the entity is the foreign key barrier
     *
     * @param string|object $entity
     *
     * @return bool
     */
    private function isForeignKeyBarrier($entity)
    {
        list($repository) = $this->getForeignInfos();

        if (!is_string($entity)) {
            $entity = get_class($entity);
        }

        return $repository->entityClass() === $entity;
    }

    /**
     * Set the foreign key value on an entity
     *
     * @param object $entity
     * @param mixed  $id
     */
    private function setForeignKeyValue($entity, $id)
    {
        list($repository, $key) = $this->getForeignInfos();

        if ($repository->entityClass() === get_class($entity)) {
            $repository->hydrateOne($entity, $key, $id);

            if ($this->isPolymorphic()) {
                $repository->hydrateOne($entity, $this->discriminator, $this->discriminatorValue);
            }
        }
    }
}
