<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexerInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * RelationInterface
 *
 * @template L as object
 * @template R as object
 */
interface RelationInterface
{
    const BELONGS_TO        = 'belongsTo';
    const HAS_ONE           = 'hasOne';
    const HAS_MANY          = 'hasMany';
    const BELONGS_TO_MANY   = 'belongsToMany';
    const BY_INHERITANCE    = 'byInheritance';
    const MORPH_TO          = 'morphTo';
    const CUSTOM            = 'custom';
//    const MORPH_TO_MANY     = 'morphToMany';

    /**
     * Get the relation repository.
     * For standard relation, this is the distant repository (i.e. related entity)
     * For polymorphic relation, this is the local repository (i.e. declarer)
     *
     * @return RepositoryInterface<R>
     */
    public function relationRepository(): RepositoryInterface;

    /**
     * Get the local (owner) repository
     *
     * @return RepositoryInterface<L>
     */
    public function localRepository(): RepositoryInterface;

    /**
     * Set the alias for to use for the joined table
     *
     * @param string|null $localAlias
     *
     * @return $this
     */
    public function setLocalAlias(?string $localAlias);

    /**
     * Set the relation options
     * 
     * @param array $options
     * 
     * @return $this
     */
    public function setOptions(array $options);

    /**
     * Load relation and inject into given entities
     *
     * Ex:
     * <code>
     * $users = EntityIndexer::formArray($mapper, User::all());
     * $relation = User::relation('customer');
     *
     * $relation->load($users); // Simply load the customer into users
     * $relation->load($users, ['packs.pack', 'parent']); // Load customer, customer packs, packs and parent customer
     * $relation->load($users, [], ['name :not' => 'John']); // Load only customers which as not the name 'John'
     * $relation->load($users, [], [], ['packs']); // Do not load packs (if marked as eager)
     * </code>
     *
     * @param EntityIndexerInterface<L> $collection Relation owners entities
     * @param string[] $with The distant relations to load. The array is in form : [ 'subrelation', 'other.subsubrelation', ... ]
     * @param mixed $constraints The distant constraints. Should be a criteria array, using relation attributes
     * @param string[] $without The distant relations to unload (in case of eager load). Format is same as $with, expects that only leaf relation are unloaded
     *
     * @return void
     * @throws PrimeException
     */
    #[ReadOperation]
    public function load(EntityIndexerInterface $collection, array $with = [], $constraints = [], array $without = []): void;

    /**
     * Load relation if not yet loaded
     *
     * - If the relation is already loaded, but sub-relations is requested ($with parameter), only sub-relations will be loaded
     * - If any constraints is found, the loading is forced
     * - If at least one of the relations is not loaded, all relations will be loaded
     *
     * Ex:
     * <code>
     * $users = EntityIndexer::formArray($mapper, User::all());
     * $relation = User::relation('customer');
     *
     * $relation->loadIfNotLoaded($users); // Simply load the customer into users
     * $relation->loadIfNotLoaded($users); // The second call will do nothing
     * $relation->loadIfNotLoaded($users, ['packs']); // Loading customers packs into customers, but customers will not be reloaded
     * </code>
     *
     * @param EntityIndexerInterface<L> $collection Relation owners entities
     * @param string[] $with The distant relations to load. The array is in form : [ 'subrelation', 'other.subsubrelation', ... ]
     * @param mixed $constraints The distant constraints. Should be a criteria array, using relation attributes
     * @param string[] $without The distant relations to unload (in case of eager load). Format is same as $with, expects that only leaf relation are unloaded
     *
     * @return void
     * @throws PrimeException
     *
     * @see RelationInterface::load() The base loading method
     */
    #[ReadOperation]
    public function loadIfNotLoaded(EntityIndexerInterface $collection, array $with = [], $constraints = [], array $without = []): void;

    /**
     * Get the distant query linked to the entity
     * The result query can be used to requests related entities
     *
     * @param L|L[] $owner The relation owner, or collection of owners
     *
     * @return ReadCommandInterface<\Bdf\Prime\Connection\ConnectionInterface, R>
     */
    public function link($owner): ReadCommandInterface;

    /**
     * Add join expression on query builder
     *
     * @param EntityJoinable&ReadCommandInterface $query
     * @param string $alias
     */
    public function join(EntityJoinable $query, string $alias): void;

    /**
     * Get the repositories to register for a JOIN query
     *
     * @see RelationInterface::join()
     * @see AliasResolver::registerMetadata()
     *
     * @param EntityJoinable $query
     * @param string $alias
     * @param string|int|null $discriminator
     *
     * @return array<string, RepositoryInterface> Repositories, indexed by alias
     */
    public function joinRepositories(EntityJoinable $query, string $alias, $discriminator = null): array;

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
     * $relation->associate($entity, $relatedEntity);
     * $entity->getRelatedEntity() === $relatedEntity; // should be true
     * </code>
     *
     * Only foreign key barrier can associate an entity
     *
     * @param L $owner  The relation owner
     * @param R $entity The related entity data
     *
     * @return L Returns the owner entity instance
     * 
     * @throws \InvalidArgumentException If the owner is not a foreign key barrier
     */
    public function associate($owner, $entity);

    /**
     * Remove the relation from owner entity
     * This is the reverse operation of associate : will detach the related entity and set the foreign key to null
     *
     * Note: This method will not perform any write operation on database :
     *       the related entity id must be generated before, and the owner must be updated manually
     *
     * Only foreign key barrier can dissociate an entity
     * 
     * @param L $owner  The relation owner
     *
     * @return L Returns the owner entity instance
     * 
     * @throws \InvalidArgumentException If the owner is not a foreign key barrier
     */
    public function dissociate($owner);

    /**
     * Add a relation entity on the given entity owner
     * This method will set the foreign key value on the related entity but not attach the related entity to the owner
     *
     * Only non foreign key barrier can add an entity
     *
     * <code>
     * $relation->add($entity, $related);
     *
     * $related->getOwnerId() === $entity->id(); // Should be true
     * </code>
     *
     * @param L $owner
     * @param R $related
     *
     * @return int
     *
     * @throws \InvalidArgumentException     If the owner is the foreign key barrier
     * @throws PrimeException When cannot save entity
     */
    #[WriteOperation]
    public function add($owner, $related): int;

    /**
     * Create the relation entity and set its foreign key value
     * This method will not attach the created entity to the owner
     *
     * Note: This method will not perform any write operation on database, it will only instantiate the entity
     *
     * <code>
     * $related = $relation->create($entity);
     * $related->getOwnerId() === $entity->id(); // Should be true
     * </code>
     *
     * Only non foreign key barrier can create an entity
     *
     * @param L $owner The relation owner
     * @param array $data The related entity data
     *
     * @return R Returns the related entity instance
     *
     * @throws \InvalidArgumentException If the owner is the foreign key barrier
     */
    public function create($owner, array $data = []);

    /**
     * Save the relation from an entity
     *
     * Note: This method can only works with attached entities
     *
     * @param L $owner
     * @param array $relations sub-relation names to save
     *
     * @return int Number of updated / inserted entities
     * @throws PrimeException When cannot save entity
     */
    #[WriteOperation]
    public function saveAll($owner, array $relations = []): int;

    /**
     * Remove the relation from an entity
     *
     * Note: This method can only works with attached entities
     *
     * @param L $owner
     * @param array $relations sub-relation names to delete
     *
     * @return int Number of deleted entities
     * @throws PrimeException When cannot delete entity
     */
    #[WriteOperation]
    public function deleteAll($owner, array $relations = []): int;

    /**
     * Check if the relation is loaded into the given entity
     *
     * @param L $entity
     *
     * @return boolean
     */
    public function isLoaded($entity): bool;

    /**
     * Clear relation entity data of the entity
     *
     * @param L $entity
     *
     * @internal
     */
    public function clearInfo($entity): void;
}
