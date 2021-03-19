<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * EntityRelation
 *
 * Relation wrapper. Link an entity to an relation object
 * 
 * @package Bdf\Prime\Relations
 *
 * @api
 * @mixin ReadCommandInterface
 * @noinspection PhpHierarchyChecksInspection
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
     * This method will set the foreign key value on the owner entity and attach the entity
     * If the relation is detached, only the foreign key will be updated
     *
     * Note: This method will not perform any write operation on database :
     *       the related entity id must be generated before, and the owner must be updated manually
     *
     * <code>
     * $entity->relation('foo')->associate($foo);
     * $entity->getFoo() === $foo; // should be true
     * </code>
     *
     * Only foreign key barrier can associate an entity
     *
     * @param object $entity The related entity data
     *
     * @return object Returns the owner entity instance
     */
    public function associate($entity)
    {
        return $this->relation->associate($this->owner, $entity);
    }

    /**
     * Remove the relation from owner entity
     * This is the reverse operation of associate : will detach the related entity and set the foreign key to null
     *
     * Note: This method will not perform any write operation on database :
     *       the related entity id must be generated before, and the owner must be updated manually
     *
     * Only foreign key barrier can dissociate an entity
     *
     * @return object Returns the owner entity instance
     */
    public function dissociate()
    {
        return $this->relation->dissociate($this->owner);
    }

    /**
     * Add a relation entity on the given entity owner
     * This method will not attach the created entity to the owner
     *
     * Note: This method will not perform any write operation on database, it will only instantiate the entity
     *
     * <code>
     * $foo = $entity->relation('foo')->create(['foo' => 'bar']);
     * $foo->getOwnerId() === $entity->id(); // Should be true
     * $foo->getFoo() === 'bar'; // Should be true
     * </code>
     *
     * Only non foreign key barrier can create an entity
     *
     * @param array $data The related entity data
     *
     * @return object Returns the related entity instance
     */
    public function create(array $data = [])
    {
        return $this->relation->create($this->owner, $data);
    }

    /**
     * Add a relation entity on the given entity owner
     * This method will set the foreign key value on the related entity but not attach the related entity to the owner
     *
     * Only non foreign key barrier can add an entity
     *
     * <code>
     * $entity->relation('foo')->($entity, $foo);
     * $foo->getOwnerId() === $entity->id(); // Should be true
     * </code>
     *
     * @param object $related
     *
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function add($related): int
    {
        return $this->relation->add($this->owner, $related);
    }

    /**
     * Check whether the owner has a distant entity relation
     * Note: only works with BelongsToMany relation
     *
     * @param string|object $related
     *
     * @return boolean
     * @throws PrimeException
     */
    #[ReadOperation]
    public function has($related): bool
    {
        /** @var BelongsToMany $this->relation */
        return $this->relation->has($this->owner, $related);
    }

    /**
     * Attach a distant entity to an entity
     * Note: only works with BelongsToMany relation
     *
     * @param string|array|object $related
     *
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function attach($related): int
    {
        /** @var BelongsToMany $this->relation */
        return $this->relation->attach($this->owner, $related);
    }

    /**
     * Detach a distant entity of an entity
     * Note: only works with BelongsToMany relation
     *
     * @param string|array|object $related
     *
     * @return int
     */
    public function detach($related): int
    {
        /** @var BelongsToMany $this->relation */
        return $this->relation->detach($this->owner, $related);
    }

    /**
     * Gets the relation query builder
     * Note: You can use magic __call method to call query methods directly
     *
     * <code>
     * $entity->relation('foo')->where(['foo' => 'bar'])->get();
     * </code>
     *
     * @return ReadCommandInterface
     */
    public function query(): ReadCommandInterface
    {
        return $this->relation->link($this->owner);
    }

    /**
     * Save the relation from an entity
     *
     * Note: This method can only works with attached entities
     *
     * @param string|array $relations sub-relation names to save
     *
     * @return int Number of updated / inserted entities
     * @throws PrimeException When cannot save entity
     */
    #[WriteOperation]
    public function saveAll($relations = []): int
    {
        return $this->relation->saveAll($this->owner, (array)$relations);
    }

    /**
     * Remove the relation from an entity
     *
     * Note: This method can only works
     *
     * @param array $relations sub-relation names to delete
     *
     * @return int Number of deleted entities
     * @throws PrimeException When cannot delete entity
     */
    #[WriteOperation]
    public function deleteAll($relations = []): int
    {
        return $this->relation->deleteAll($this->owner, (array)$relations);
    }

    /**
     * Check if the relation is loaded on the current entity
     *
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->relation->isLoaded($this->owner);
    }

    /**
     * Redirect every call to the relation query Builder
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->query()->$name(...$arguments);
    }
}
