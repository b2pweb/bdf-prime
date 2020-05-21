<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Query\QueryInterface;

/**
 * EntityRelation
 *
 * Relation wrapper. Link an entity to an relation object
 * 
 * @package Bdf\Prime\Relations
 *
 * @api
 */
class EntityRelation
{
    /**
     * The entity owner of the relation
     *
     * @var object
     */
    protected $owner;

    /**
     * The relation
     *
     * @var RelationInterface
     */
    protected $relation;

    /**
     * EntityRelation constructor.
     *
     * @param object                 $owner     The relation owner
     * @param RelationInterface      $relation  The relation
     */
    public function __construct($owner, RelationInterface $relation)
    {
        $this->owner    = $owner;
        $this->relation = $relation;
    }

    /**
     * Associate an entity to the owner entity
     *
     * @param object     $entity The related entity data
     *
     * @return object    Returns the owner entity instance
     */
    public function associate($entity)
    {
        return $this->relation->associate($this->owner, $entity);
    }
    
    /**
     * Remove the relation from owner entity
     *
     * @return object  Returns the owner entity instance
     */
    public function dissociate()
    {
        return $this->relation->dissociate($this->owner);
    }

    /**
     * Add a relation entity on the given entity owner
     *
     * @param array $data   The related entity data
     *
     * @return object       Returns the related entity instance
     */
    public function create(array $data = [])
    {
        return $this->relation->create($this->owner, $data);
    }

    /**
     * Add a relation entity on the given entity owner
     *
     * @param object $related
     *
     * @return int
     */
    public function add($related)
    {
        return $this->relation->add($this->owner, $related);
    }

    /**
     * Check whether the owner has a distant entity relation
     *
     * @param string|object  $related
     *
     * @return boolean
     */
    public function has($related)
    {
        return $this->relation->has($this->owner, $related);
    }

    /**
     * Attach a distant entity to an entity
     *
     * @param string|array|object   $related
     *
     * @return int
     */
    public function attach($related)
    {
        return $this->relation->attach($this->owner, $related);
    }

    /**
     * Detach a distant entity of an entity
     *
     * @param string|array|object   $related
     *
     * @return int
     */
    public function detach($related)
    {
        return $this->relation->detach($this->owner, $related);
    }

    /**
     * Gets the relation query builder
     *
     * @return QueryInterface
     */
    public function query()
    {
        return $this->relation->link($this->owner);
    }

    /**
     * Save the relation from an entity
     *
     * @param string|array $relations
     *
     * @return int
     */
    public function saveAll($relations = [])
    {
        return $this->relation->saveAll($this->owner, (array)$relations);
    }

    /**
     * Remove the relation from an entity
     *
     * @param string|array $relations
     *
     * @return int
     */
    public function deleteAll($relations = [])
    {
        return $this->relation->deleteAll($this->owner, (array)$relations);
    }

    /**
     * Check if the relation is loaded on the current entity
     *
     * @return bool
     */
    public function isLoaded()
    {
        return $this->relation->isLoaded($this->owner);
    }

    /**
     * Redirect every call to the relation query Builder
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return QueryInterface
     */
    public function __call($name, $arguments)
    {
        return $this->query()->$name(...$arguments);
    }
}
