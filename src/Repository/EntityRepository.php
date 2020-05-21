<?php

namespace Bdf\Prime\Repository;

use Bdf\Event\EventNotifier;
use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Collection\Indexer\SingleEntityIndexer;
use Bdf\Prime\Connection\Event\ConnectionClosedListenerInterface;
use Bdf\Prime\Entity\Criteria;
use Bdf\Prime\Events;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\QueryRepositoryExtension;
use Bdf\Prime\Relations\EntityRelation;
use Bdf\Prime\Relations\Relation;
use Bdf\Prime\Relations\RelationInterface;
use Bdf\Prime\Repository\Write\Writer;
use Bdf\Prime\Repository\Write\WriterInterface;
use Bdf\Prime\Schema\NullResolver;
use Bdf\Prime\Schema\Resolver;
use Bdf\Prime\ServiceLocator;
use Doctrine\Common\EventSubscriber;

/**
 * Db repository
 * 
 * implementation de l'abstraction d'un dépot de données.
 * 
 * @todo fix: il est possible de desactiver temporairement le cache sur des methodes d ecriture
 *
 * @package Bdf\Prime\Repository
 *
 * @mixin RepositoryQueryFactory
 */
class EntityRepository implements RepositoryInterface, EventSubscriber, ConnectionClosedListenerInterface
{
    use EventNotifier;
    
    /**
     * @var Mapper 
     */
    protected $mapper;
    
    /**
     * @var ServiceLocator 
     */
    protected $serviceLocator;
    
    /**
     * Query result cache
     * 
     * @var CacheInterface
     */
    protected $resultCache;
    
    /**
     * Disable the global constraints for one query
     * 
     * @var bool
     */
    protected $withoutConstraints = false;
    
    /**
     * Cache of relation instance
     * 
     * @var RelationInterface[]
     */
    protected $relations = [];

    /**
     * The collection factory
     *
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var RepositoryQueryFactory
     */
    protected $queries;

    /**
     * @var WriterInterface
     */
    protected $writer;


    /**
     * Constructor
     * 
     * @param Mapper         $mapper
     * @param ServiceLocator $serviceLocator
     * @param CacheInterface $cache
     */
    public function __construct(Mapper $mapper, ServiceLocator $serviceLocator, CacheInterface $cache = null)
    {
        $this->resultCache = $cache;
        $this->mapper = $mapper;
        $this->serviceLocator = $serviceLocator;

        $this->collectionFactory = CollectionFactory::forRepository($this);
        $this->queries = new RepositoryQueryFactory($this, $cache);
        $this->writer = new Writer($this, $serviceLocator);

        $this->mapper->events($this);
        $this->connection()->getEventManager()->addEventSubscriber($this);
    }
    
    /**
     * {@inheritdoc}
     */
    public function repository($entity)
    {
        return $this->serviceLocator->repository($entity);
    }
    
    /**
     * {@inheritdoc}
     */
    public function mapper()
    {
        return $this->mapper;
    }
    
    /**
     * {@inheritdoc}
     */
    public function metadata()
    {
        return $this->mapper->metadata();
    }
    
    /**
     * {@inheritdoc}
     */
    public function isReadOnly()
    {
        return $this->mapper->isReadOnly();
    }
    
    /**
     * {@inheritdoc}
     */
    public function criteria(array $criteria = [])
    {
        return new Criteria($criteria);
    }
    
    /**
     * {@inheritdoc}
     */
    public function entity(array $data = [])
    {
        return $this->mapper->entity($data);
    }
    
    /**
     * {@inheritdoc}
     */
    public function entityName()
    {
        return $this->mapper->metadata()->entityName;
    }

    /**
     * {@inheritdoc}
     */
    public function entityClass()
    {
        return $this->mapper->metadata()->entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function collection(array $entities = [])
    {
        return new EntityCollection($this, $entities);
    }

    /**
     * {@inheritdoc}
     */
    public function collectionFactory()
    {
        return $this->collectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateOne($entity, $property, $value)
    {
        $this->mapper->hydrateOne($entity, $property, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function extractOne($entity, $property)
    {
        return $this->mapper->extractOne($entity, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function connection()
    {
        return $this->serviceLocator->connection($this->mapper->metadata()->connection);
    }
    
    /**
     * Set the new connection for next queries
     * 
     * If work is set, the connection will be available only for the work content
     * Else the repository will be linked with this new connection
     * 
     * @param string   $connection
     * @param \Closure $work
     * 
     * @return $this|mixed  Returns the work result if set or the instance if not set
     */
    public function on($connection, \Closure $work = null)
    {
        $original = $this->changeActiveConnection($connection);

        if ($work !== null) {
            try {
                return $work($this);
            } finally {
                $this->changeActiveConnection($original);
            }
        }
        
        return $this;
    }
    
    /**
     * Launch transactionnal queries
     * 
     * @param \Closure $work
     * @return mixed
     * 
     * @throws \Exception
     */
    public function transaction(\Closure $work)
    {
        $connection = $this->connection();
        
        try {
            $connection->beginTransaction();
            
            $result = $work($this);
            
            if ($result === false) {
                $connection->rollback();
            } else {
                $connection->commit();
            }
        } catch (\Exception $e) {
            $connection->rollback();
            
            throw $e;
        }
        
        return $result;
    }
    
    /**
     * Disable the cache result.
     * +Be aware+ The cache should be disabled only for select queries.
     *
     * @return $this
     *
     * @deprecated since 1.5 Use Cachable::disableCache()
     * @see Cachable::disableCache()
     */
    public function disableCache()
    {
        $this->queries->disableCache();
        
        return $this;
    }
    
    /**
     * Load relations on given entity
     * If the relation is already loaded, the relation will not be reloaded
     * Use reloadRelation for force loading
     * 
     * @param object          $entity
     * @param string|array    $relations
     *
     * @return void
     *
     * @see EntityRepository::reloadRelations() For force load relations
     */
    public function loadRelations($entity, $relations)
    {
        foreach (Relation::sanitizeRelations((array)$relations) as $relationName => $meta) {
            $this->relation($relationName)->loadIfNotLoaded(
                new SingleEntityIndexer($this->mapper, $entity),
                $meta['relations'],
                $meta['constraints']
            );
        }
    }

    /**
     * Force loading relations on given entity
     *
     * @param object          $entity
     * @param string|array    $relations
     *
     * @return void
     *
     * @see EntityRepository::loadRelations() For loading relation only if not yet loaded
     */
    public function reloadRelations($entity, $relations)
    {
        foreach (Relation::sanitizeRelations((array)$relations) as $relationName => $meta) {
            $this->relation($relationName)->load(
                new SingleEntityIndexer($this->mapper, $entity),
                $meta['relations'],
                $meta['constraints']
            );
        }
    }

    /**
     * Get a entity relation wrapper linked to the entity
     *
     * @param string   $relationName
     * @param object   $entity
     *
     * @return EntityRelation
     */
    public function onRelation($relationName, $entity)
    {
        return new EntityRelation($entity, $this->relation($relationName));
    }

    /**
     * {@inheritdoc}
     */
    public function relation($relationName)
    {
        if (!isset($this->relations[$relationName])) {
            $this->relations[$relationName] = Relation::make($this, $relationName, $this->mapper->relation($relationName));
        }
        
        return $this->relations[$relationName];
    }

    /**
     * Save entity and its relations
     *
     * @param object         $entity
     * @param string|array   $relations
     *
     * @return int
     */
    public function saveAll($entity, $relations)
    {
        $relations = Relation::sanitizeRelations((array)$relations);

        return $this->transaction(function() use($entity, $relations) {
            $nb = $this->save($entity);

            foreach ((array)$relations as $relationName => $info) {
                $nb += $this->relation($relationName)->saveAll($entity, $info['relations']);
            }

            return $nb;
        });
    }

    /**
     * Delete entity and its relations
     *
     * @param object         $entity
     * @param string|array   $relations
     *
     * @return int
     */
    public function deleteAll($entity, $relations)
    {
        $relations = Relation::sanitizeRelations((array)$relations);

        return $this->transaction(function() use($entity, $relations) {
            $nb = $this->delete($entity);

            foreach ((array)$relations as $relationName => $info) {
                $nb += $this->relation($relationName)->deleteAll($entity, $info['relations']);
            }

            return $nb;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function constraints($context = null)
    {
        if ($this->withoutConstraints === true) {
            $this->withoutConstraints = false;
            return [];
        }

        $constraints = $this->metadata()->constraints;

        if ($context && is_array($constraints)) {
            $context .= '.';
            foreach ($constraints as $key => $value) {
                $constraints[$context.$key] = $value;
                unset($constraints[$key]);
            }
        }

        return $constraints;
    }
    
    /**
     * Disable global constraints on this repository.
     * Only for the current query
     * 
     * @return $this
     */
    public function withoutConstraints()
    {
        $this->withoutConstraints = true;

        return $this;
    }

    /**
     * Check whether the current query has global constraints
     *
     * @return bool
     */
    public function isWithoutConstraints()
    {
        return $this->withoutConstraints;
    }

    /**
     * Get query builder
     * 
     * @return QueryInterface
     */
    public function builder()
    {
        return $this->queries->builder();
    }

    /**
     * {@inheritdoc}
     */
    public function queries()
    {
        return $this->queries;
    }

    /**
     * {@inheritdoc}
     */
    public function writer()
    {
        return $this->writer;
    }

    /**
     * Count entity
     * 
     * @param array $criteria
     * @param string|array $attributes
     * 
     * @return int
     */
    public function count(array $criteria = [], $attributes = null)
    {
        return $this->builder()->where($criteria)->count($attributes);
    }

    /**
     * Assert that entity exists in repository
     *
     * @param object $entity
     *
     * @return boolean
     */
    public function exists($entity)
    {
        return $this->queries->countKeyValue($this->mapper()->primaryCriteria($entity)) > 0;
    }

    /**
     * Refresh the entity form the repository
     *
     * @param object $entity
     * @param array  $criteria
     *
     * @return object           The refreshed object
     */
    public function refresh($entity, array $criteria = [])
    {
        if (empty($criteria)) {
            return $this->queries->findById($this->mapper()->primaryCriteria($entity));
        }

        $criteria += $this->mapper()->primaryCriteria($entity);

        return $this->builder()->where($criteria)->first();
    }

    /**
     * Check if the entity is new
     *
     * @param object $entity
     *
     * @return bool|null     Retuns null if entity is composite primary
     */
    public function isNew($entity)
    {
        $metadata = $this->mapper->metadata();

        if ($metadata->isCompositePrimaryKey()) {
            return null;
        }

        $primaryValue = $this->mapper->getId($entity);

        if (empty($primaryValue)) {
            return true;
        }

        return $metadata->isForeignPrimaryKey() ? null : false;
    }

    /**
     * Insert or update an entity
     * 
     * @param object $entity
     * 
     * @return int
     */
    public function save($entity)
    {
        $isNew = $this->isNew($entity);

        if ($this->notify(Events::PRE_SAVE, [$entity, $this, $isNew]) === false) {
            return 0;
        }

        // composite primary
        if ($isNew === null) {
            $count = $this->replace($entity);
        } elseif ($isNew) {
            $count = $this->insert($entity);
        } else {
            $count = $this->update($entity);
        }

        $this->notify(Events::POST_SAVE, [$entity, $this, $count, $isNew]);
        
        return $count;
    }

    /**
     * Replace an entity
     *
     * @param object $entity
     *
     * @return int  Returns 2 if updated and 1 if inserting
     */
    public function replace($entity)
    {
        $isNew = $this->isNew($entity);

        if ($isNew !== true && $this->exists($entity)) {
            return $this->update($entity) + 1;
        }

        return $this->insert($entity);
    }

    /**
     * Duplicate an entity
     * remove primary key and launch insertion
     * 
     * @param object $entity
     * 
     * @return int
     */
    public function duplicate($entity)
    {
        $this->mapper()->setId($entity, null);
        
        return $this->insert($entity);
    }
    
    /**
     * Insert an entity
     * 
     * @param object $entity
     * @param bool   $ignore
     * 
     * @return int
     */
    public function insert($entity, $ignore = false)
    {
        return $this->writer->insert($entity, ['ignore' => $ignore]);
    }

    /**
     * Insert ignore
     * 
     * @param object $entity
     * 
     * @return int
     */
    public function insertIgnore($entity)
    {
        return $this->insert($entity, true);
    }
    
    /**
     * Update an entity
     *
     * @param object $entity
     * @param array  $attributes
     *
     * @return int
     */
    public function update($entity, array $attributes = null)
    {
        return $this->writer->update($entity, ['attributes' => $attributes]);
    }

    /**
     * Update collection of entities
     * 
     * @param array $attributes
     * @param array $criteria
     * 
     * @return int
     */
    public function updateBy(array $attributes, array $criteria = [])
    {
        return $this->builder()->where($criteria)->update($attributes);
    }
    
    /**
     * Remove a entity
     * 
     * @param object $entity
     * 
     * @return int
     */
    public function delete($entity)
    {
        return $this->writer->delete($entity);
    }
    
    /**
     * Remove a collection of entities
     * 
     * @param array $criteria
     * 
     * @return int
     */
    public function deleteBy(array $criteria)
    {
        return $this->builder()->where($criteria)->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function schema($force = false)
    {
        if (!$this->mapper->hasSchemaManager() && !$force) {
            return new NullResolver();
        }
        
        return new Resolver($this->serviceLocator, $this->mapper->metadata());
    }
    
    /**
     * Gets custom filters
     *
     * @return array
     */
    public function filters()
    {
        return $this->mapper->filters();
    }
    
    /**
     * Repository extensions
     *
     * @return array
     */
    public function scopes()
    {
        return $this->mapper->scopes();
    }
    
    //----- events

    /**
     * Register post load event
     *
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return $this
     */
    public function loaded(callable $listener, $once = false)
    {
        if ($once) {
            $this->once(Events::POST_LOAD, $listener);
        } else {
            $this->listen(Events::POST_LOAD, $listener);
        }

        return $this;
    }

    /**
     * Register pre save event
     *
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return $this
     */
    public function saving(callable $listener, $once = false)
    {
        if ($once) {
            $this->once(Events::PRE_SAVE, $listener);
        } else {
            $this->listen(Events::PRE_SAVE, $listener);
        }

        return $this;
    }

    /**
     * Register post save event
     *
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return $this
     */
    public function saved(callable $listener, $once = false)
    {
        if ($once) {
            $this->once(Events::POST_SAVE, $listener);
        } else {
            $this->listen(Events::POST_SAVE, $listener);
        }

        return $this;
    }

    /**
     * Register post insert event
     *
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return $this
     */
    public function inserting(callable $listener, $once = false)
    {
        if ($once) {
            $this->once(Events::PRE_INSERT, $listener);
        } else {
            $this->listen(Events::PRE_INSERT, $listener);
        }

        return $this;
    }

    /**
     * Register post insert event
     *
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return $this
     */
    public function inserted(callable $listener, $once = false)
    {
        if ($once) {
            $this->once(Events::POST_INSERT, $listener);
        } else {
            $this->listen(Events::POST_INSERT, $listener);
        }

        return $this;
    }

    /**
     * Register post update event
     *
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return $this
     */
    public function updating(callable $listener, $once = false)
    {
        if ($once) {
            $this->once(Events::PRE_UPDATE, $listener);
        } else {
            $this->listen(Events::PRE_UPDATE, $listener);
        }

        return $this;
    }

    /**
     * Register post update event
     *
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return $this
     */
    public function updated(callable $listener, $once = false)
    {
        if ($once) {
            $this->once(Events::POST_UPDATE, $listener);
        } else {
            $this->listen(Events::POST_UPDATE, $listener);
        }

        return $this;
    }

    /**
     * Register post delete event
     *
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return $this
     */
    public function deleting(callable $listener, $once = false)
    {
        if ($once) {
            $this->once(Events::PRE_DELETE, $listener);
        } else {
            $this->listen(Events::PRE_DELETE, $listener);
        }

        return $this;
    }

    /**
     * Register post delete event
     *
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return $this
     */
    public function deleted(callable $listener, $once = false)
    {
        if ($once) {
            $this->once(Events::POST_DELETE, $listener);
        } else {
            $this->listen(Events::POST_DELETE, $listener);
        }

        return $this;
    }

    /**
     * Query method
     * redirect method to the query builder
     * 
     * @param string $name         Query builder method
     * @param array  $arguments
     * 
     * @return int|QueryInterface
     */
    public function __call($name, $arguments)
    {
        return $this->queries->$name(...$arguments);
    }
    
    //--- Methodes for optimisation: alias of query methods
    
    /**
     * @see QueryRepositoryExtension::with
     * 
     * @param string|array $relations
     * 
     * @return QueryInterface
     */
    public function with($relations)
    {
        return $this->builder()->with($relations);
    }

    /**
     * @see QueryRepositoryExtension::without
     *
     * @param string|array $relations
     *
     * @return QueryInterface
     */
    public function without($relations)
    {
        return $this->builder()->without($relations);
    }
    
    /**
     * @see QueryRepositoryExtension::by
     * 
     * @param string|array $attribute
     * @param boolean      $combine
     * 
     * @return QueryInterface
     */
    public function by($attribute, $combine = false)
    {
        return $this->builder()->by($attribute, $combine);
    }
    
    /**
     * @see QueryInterface::wrapAs
     * 
     * @param string $wrapperClass
     * 
     * @return QueryInterface
     */
    public function wrapAs($wrapperClass)
    {
        return $this->builder()->wrapAs($wrapperClass);
    }
    
    /**
     * @param array $criteria
     * @param array $attributes
     * 
     * @return array
     */
    public function find(array $criteria, $attributes = null)
    {
        return $this->builder()->find($criteria, $attributes);
    }
    
    /**
     * @param array $criteria
     * @param array $attributes
     * 
     * @return object
     */
    public function findOne(array $criteria, $attributes = null)
    {
        return $this->builder()->findOne($criteria, $attributes);
    }
    
    /**
     * @see QueryInterface::where
     * 
     * @param string|array $relations
     * 
     * @return QueryInterface
     */
    public function where($column, $operator = null, $value = null)
    {
        return $this->builder()->where($column, $operator, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function onConnectionClosed()
    {
        $this->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [ConnectionClosedListenerInterface::EVENT_NAME];
    }

    /**
     * Free metadata information on the given entity
     * Note: This method is called by the model destructor
     *
     * @param object $entity
     *
     * @return void
     */
    public function free($entity)
    {
        foreach ($this->relations as $relation) {
            $relation->clearInfo($entity);
        }
    }

    /**
     * Clear dependencies for break cyclic references
     * After this call, the repository will be unusable
     *
     * @internal
     */
    public function destroy()
    {
        $this->connection()->getEventManager()->removeEventSubscriber($this);

        $this->serviceLocator = null;
        $this->queries = null;
        $this->writer = null;
        $this->relations = [];
        $this->collectionFactory = null;

        $this->mapper->destroy();
        $this->mapper = null;

        if ($this->resultCache) {
            $this->resultCache->clear();
            $this->resultCache = null;
        }
    }

    /**
     * Change the active connection on the repository
     * All queries will be reseted
     *
     * /!\ The method will not check if the connection exists nor the new connection is same as the active
     *
     * @param string $connectionName The new connection name
     *
     * @return string The last active connection name
     */
    private function changeActiveConnection($connectionName)
    {
        $this->connection()->getEventManager()->removeEventSubscriber($this);

        $original = $this->mapper->metadata()->connection;
        $this->mapper->metadata()->connection = $connectionName;

        $this->connection()->getEventManager()->addEventSubscriber($this);
        $this->reset();

        return $original;
    }

    /**
     * Reset the inner queries
     * Use for invalidate prepared queries, or when connection changed
     */
    private function reset()
    {
        // Reset queries
        $this->queries = new RepositoryQueryFactory($this, $this->resultCache);
        $this->writer = new Writer($this, $this->serviceLocator);
        $this->relations = []; // Relation may contains inner query : it must be reseted

        if ($this->resultCache) {
            $this->resultCache->clear();
        }
    }
}
