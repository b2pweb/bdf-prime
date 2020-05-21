<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexerInterface;
use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * RelationInterface
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
     * @return RepositoryInterface
     */
    public function relationRepository();

    /**
     * Get the local repository
     *
     * @return RepositoryInterface
     */
    public function localRepository();

    /**
     * Set the alias for to use for the joined table
     *
     * @param string $localAlias
     *
     * @return $this
     */
    public function setLocalAlias($localAlias);

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
     * @param EntityIndexerInterface $collection Relation owners entities
     * @param string[] $with The distant relations to load. The array is in form : [ 'subrelation', 'other.subsubrelation', ... ]
     * @param mixed $constraints The distant constraints. Should be a criteria array, using relation attributes
     * @param string[] $without The distant relations to unload (in case of eager load). Format is same as $with, expects that only leaf relation are unloaded
     *
     * @return void
     */
    public function load(EntityIndexerInterface $collection, array $with = [], $constraints = [], array $without = []);

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
     * @param EntityIndexerInterface $collection Relation owners entities
     * @param string[] $with The distant relations to load. The array is in form : [ 'subrelation', 'other.subsubrelation', ... ]
     * @param mixed $constraints The distant constraints. Should be a criteria array, using relation attributes
     * @param string[] $without The distant relations to unload (in case of eager load). Format is same as $with, expects that only leaf relation are unloaded
     *
     * @return void
     *
     * @see RelationInterface::load() The base loading method
     */
    public function loadIfNotLoaded(EntityIndexerInterface $collection, array $with = [], $constraints = [], array $without = []);

    /**
     * Get the distant query linked to the entity
     *
     * @param object|object[] $owner The relation owner, or collection of owners
     *
     * @return QueryInterface
     */
    public function link($owner);
    
    /**
     * Add join expression on query builder
     *
     * @param QueryInterface $query
     * @param string       $alias
     */
    public function join($query, $alias = null);

    /**
     * Get the repositories to register for a JOIN query
     *
     * @see RelationInterface::join()
     * @see AliasResolver::registerMetadata()
     *
     * @param EntityJoinable $query
     * @param string|null $alias
     * @param string $discriminator
     *
     * @return \Bdf\Prime\Repository\RepositoryInterface[] Repositories, indexed by alias
     */
    public function joinRepositories(EntityJoinable $query, $alias = null, $discriminator = null);

    /**
     * Associate an entity to the owner entity
     * 
     * Only foreign key barrier can associate an entity
     *
     * @param object $owner  The relation owner
     * @param object $entity The related entity data
     *
     * @return object                        Returns the owner entity instance
     * 
     * @throws \InvalidArgumentException     If the owner is not a foreign key barrier
     */
    public function associate($owner, $entity);
    
    /**
     * Remove the relation from owner entity
     *
     * Only foreign key barrier can dissociate an entity
     * 
     * @param object $owner  The relation owner
     *
     * @return object                        Returns the owner entity instance
     * 
     * @throws \InvalidArgumentException     If the owner is not a foreign key barrier
     */
    public function dissociate($owner);
    
    /**
     * Add a relation entity on the given entity owner
     *
     * Only non foreign key barrier can create an entity
     * 
     * @param object $owner  The relation owner
     * @param array  $data   The related entity data
     *
     * @return object                        Returns the related entity instance
     * 
     * @throws \InvalidArgumentException     If the owner is the foreign key barrier
     */
    public function create($owner, array $data = []);

    /**
     * Add a relation entity on the given entity owner
     *
     * Only non foreign key barrier can add an entity
     * 
     * @param object $owner
     * @param object $related
     *
     * @return int
     * 
     * @throws \InvalidArgumentException     If the owner is the foreign key barrier
     */
    public function add($owner, $related);

    /**
     * Save the relation from an entity
     *
     * @param object $owner
     * @param array  $relations
     *
     * @return int
     */
    public function saveAll($owner, array $relations = []);

    /**
     * Remove the relation from an entity
     *
     * @param object $owner
     * @param array  $relations
     *
     * @return int
     */
    public function deleteAll($owner, array $relations = []);

    /**
     * Check if the relation is loaded into the given entity
     *
     * @param object $entity
     *
     * @return boolean
     */
    public function isLoaded($entity);

    public function clearInfo($entity);
}
