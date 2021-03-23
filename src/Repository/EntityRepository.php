<?php

namespace Bdf\Prime\Repository;

use BadMethodCallException;
use Bdf\Event\EventNotifier;
use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Collection\Indexer\SingleEntityIndexer;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Event\ConnectionClosedListenerInterface;
use Bdf\Prime\Connection\TransactionManagerInterface;
use Bdf\Prime\Entity\Criteria;
use Bdf\Prime\Events;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\QueryRepositoryExtension;
use Bdf\Prime\Relations\EntityRelation;
use Bdf\Prime\Relations\Relation;
use Bdf\Prime\Relations\RelationInterface;
use Bdf\Prime\Repository\Write\Writer;
use Bdf\Prime\Repository\Write\WriterInterface;
use Bdf\Prime\Schema\NullResolver;
use Bdf\Prime\Schema\Resolver;
use Bdf\Prime\Schema\ResolverInterface;
use Bdf\Prime\ServiceLocator;
use Closure;
use Doctrine\Common\EventSubscriber;
use Exception;

/**
 * Db repository
 * 
 * implementation de l'abstraction d'un dépot de données.
 * 
 * @todo fix: il est possible de desactiver temporairement le cache sur des methodes d ecriture
 *
 * @package Bdf\Prime\Repository
 *
 * @template E as object
 * @implements RepositoryInterface<E>
 * @implements RepositoryEventsSubscriberInterface<E>
 *
 * @mixin RepositoryQueryFactory<E>
 */
class EntityRepository implements RepositoryInterface, EventSubscriber, ConnectionClosedListenerInterface, RepositoryEventsSubscriberInterface
{
    use EventNotifier;
    
    /**
     * @var Mapper<E>
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
     * @var RepositoryQueryFactory<E>
     */
    protected $queries;

    /**
     * @var WriterInterface<E>
     */
    protected $writer;


    /**
     * Constructor
     * 
     * @param Mapper<E> $mapper
     * @param ServiceLocator $serviceLocator
     * @param CacheInterface|null $cache
     */
    public function __construct(Mapper $mapper, ServiceLocator $serviceLocator, ?CacheInterface $cache = null)
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
    public function repository($entity): ?RepositoryInterface
    {
        return $this->serviceLocator->repository($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function mapper(): Mapper
    {
        return $this->mapper;
    }
    
    /**
     * {@inheritdoc}
     */
    public function metadata(): Metadata
    {
        return $this->mapper->metadata();
    }
    
    /**
     * {@inheritdoc}
     */
    public function isReadOnly(): bool
    {
        return $this->mapper->isReadOnly();
    }
    
    /**
     * {@inheritdoc}
     */
    public function criteria(array $criteria = []): Criteria
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
    public function entityName(): string
    {
        return $this->mapper->metadata()->entityName;
    }

    /**
     * {@inheritdoc}
     */
    public function entityClass(): string
    {
        return $this->mapper->metadata()->entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function collection(array $entities = []): CollectionInterface
    {
        return new EntityCollection($this, $entities);
    }

    /**
     * {@inheritdoc}
     */
    public function collectionFactory(): CollectionFactory
    {
        return $this->collectionFactory;
    }

    /**
     * Hydrate on property value of an entity
     *
     * @param E $entity
     * @param string $property
     * @param mixed  $value
     *
     * @return void
     *
     * @see Mapper::hydrateOne()
     */
    public function hydrateOne($entity, string $property, $value): void
    {
        $this->mapper->hydrateOne($entity, $property, $value);
    }

    /**
     * Get attribute value of an entity
     *
     * @param E $entity
     * @param string $property
     *
     * @return mixed
     *
     * @see Mapper::extractOne()
     */
    public function extractOne($entity, string $property)
    {
        return $this->mapper->extractOne($entity, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function connection(): ConnectionInterface
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
     * @param Closure|null $work
     * 
     * @return $this|mixed  Returns the work result if set or the instance if not set
     */
    public function on($connection, ?Closure $work = null)
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
     * Launch transactional queries
     * 
     * @param callable(EntityRepository):R $work
     * @return R
     * 
     * @throws Exception
     * @throws PrimeException
     *
     * @template R
     */
    public function transaction(callable $work)
    {
        $connection = $this->connection();

        if (!$connection instanceof TransactionManagerInterface) {
            throw new BadMethodCallException('Transactions are not supported by the connection '.$connection->getName());
        }

        // @todo handle Doctrine DBAL Exception ?
        // @todo transaction method on connection ?
        try {
            $connection->beginTransaction();

            $result = $work($this);

            if ($result === false) {
                $connection->rollback();
            } else {
                $connection->commit();
            }
        } catch (Exception $e) {
            $connection->rollback();

            throw $e;
        }

        return $result;
    }

    /**
     * Load relations on given entity
     * If the relation is already loaded, the relation will not be reloaded
     * Use reloadRelation for force loading
     * 
     * @param E $entity
     * @param string|array $relations
     *
     * @return void
     * @throws PrimeException
     *
     * @see EntityRepository::reloadRelations() For force load relations
     */
    #[ReadOperation]
    public function loadRelations($entity, $relations): void
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
     * @param E $entity
     * @param string|array $relations
     *
     * @return void
     * @throws PrimeException
     *
     * @see EntityRepository::loadRelations() For loading relation only if not yet loaded
     */
    #[ReadOperation]
    public function reloadRelations($entity, $relations): void
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
     * @param string $relationName
     * @param E $entity
     *
     * @return EntityRelation
     * @todo template
     */
    public function onRelation(string $relationName, $entity): EntityRelation
    {
        return new EntityRelation($entity, $this->relation($relationName));
    }

    /**
     * {@inheritdoc}
     */
    public function relation(string $relationName): RelationInterface
    {
        if (!isset($this->relations[$relationName])) {
            $this->relations[$relationName] = Relation::make($this, $relationName, $this->mapper->relation($relationName));
        }
        
        return $this->relations[$relationName];
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function saveAll($entity, $relations): int
    {
        $relations = Relation::sanitizeRelations((array)$relations);

        return $this->transaction(function() use($entity, $relations) {
            $nb = $this->save($entity);

            foreach ($relations as $relationName => $info) {
                $nb += $this->relation($relationName)->saveAll($entity, $info['relations']);
            }

            return $nb;
        });
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function deleteAll($entity, $relations): int
    {
        $relations = Relation::sanitizeRelations((array)$relations);

        return $this->transaction(function() use($entity, $relations) {
            $nb = $this->delete($entity);

            foreach ($relations as $relationName => $info) {
                $nb += $this->relation($relationName)->deleteAll($entity, $info['relations']);
            }

            return $nb;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function constraints(string $context = null): array
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
    public function isWithoutConstraints(): bool
    {
        return $this->withoutConstraints;
    }

    /**
     * Get query builder
     * 
     * @return QueryInterface<ConnectionInterface, E>
     */
    public function builder()
    {
        return $this->queries->builder();
    }

    /**
     * {@inheritdoc}
     */
    public function queries(): RepositoryQueryFactory
    {
        return $this->queries;
    }

    /**
     * {@inheritdoc}
     */
    public function writer(): WriterInterface
    {
        return $this->writer;
    }

    /**
     * Count entity
     * 
     * @param array $criteria
     * @param string|array|null $attributes
     * 
     * @return int
     * @throws PrimeException
     */
    #[ReadOperation]
    public function count(array $criteria = [], $attributes = null): int
    {
        /** @psalm-suppress UndefinedInterfaceMethod */
        return $this->builder()->where($criteria)->count($attributes);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function exists($entity): bool
    {
        return $this->queries->countKeyValue($this->mapper()->primaryCriteria($entity)) > 0;
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
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
     * @param E $entity
     *
     * @return bool|null Returns null if entity is composite primary
     */
    public function isNew($entity): ?bool
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
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function save($entity): int
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
     * @param E $entity
     *
     * @return int  Returns 2 if updated and 1 if inserting
     * @throws PrimeException
     */
    #[WriteOperation]
    public function replace($entity): int
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
     * @param E $entity
     * 
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function duplicate($entity): int
    {
        $this->mapper()->setId($entity, null);
        
        return $this->insert($entity);
    }
    
    /**
     * Insert an entity
     * 
     * @param E $entity
     * @param bool $ignore
     * 
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function insert($entity, bool $ignore = false): int
    {
        return $this->writer->insert($entity, ['ignore' => $ignore]);
    }

    /**
     * Insert ignore
     * 
     * @param E $entity
     * 
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function insertIgnore($entity): int
    {
        return $this->insert($entity, true);
    }
    
    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function update($entity, array $attributes = null): int
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
     * @throws PrimeException
     */
    #[WriteOperation]
    public function updateBy(array $attributes, array $criteria = []): int
    {
        return $this->builder()->where($criteria)->update($attributes);
    }
    
    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function delete($entity): int
    {
        return $this->writer->delete($entity);
    }
    
    /**
     * Remove a collection of entities
     * 
     * @param array $criteria
     * 
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function deleteBy(array $criteria): int
    {
        return $this->builder()->where($criteria)->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function schema(bool $force = false): ResolverInterface
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
     * {@inheritdoc}
     */
    public function loaded(callable $listener, bool $once = false)
    {
        if ($once) {
            $this->once(Events::POST_LOAD, $listener);
        } else {
            $this->listen(Events::POST_LOAD, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function saving(callable $listener, bool $once = false)
    {
        if ($once) {
            $this->once(Events::PRE_SAVE, $listener);
        } else {
            $this->listen(Events::PRE_SAVE, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function saved(callable $listener, bool $once = false)
    {
        if ($once) {
            $this->once(Events::POST_SAVE, $listener);
        } else {
            $this->listen(Events::POST_SAVE, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function inserting(callable $listener, bool $once = false)
    {
        if ($once) {
            $this->once(Events::PRE_INSERT, $listener);
        } else {
            $this->listen(Events::PRE_INSERT, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function inserted(callable $listener, bool $once = false)
    {
        if ($once) {
            $this->once(Events::POST_INSERT, $listener);
        } else {
            $this->listen(Events::POST_INSERT, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function updating(callable $listener, bool $once = false)
    {
        if ($once) {
            $this->once(Events::PRE_UPDATE, $listener);
        } else {
            $this->listen(Events::PRE_UPDATE, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function updated(callable $listener, bool $once = false)
    {
        if ($once) {
            $this->once(Events::POST_UPDATE, $listener);
        } else {
            $this->listen(Events::POST_UPDATE, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deleting(callable $listener, bool $once = false)
    {
        if ($once) {
            $this->once(Events::PRE_DELETE, $listener);
        } else {
            $this->listen(Events::PRE_DELETE, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deleted(callable $listener, bool $once = false)
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
     * @return QueryInterface<ConnectionInterface, E>
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
     * @return QueryInterface<ConnectionInterface, E>
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
     * @return QueryInterface<ConnectionInterface, E>
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
     * @return QueryInterface<ConnectionInterface, E>
     */
    public function wrapAs($wrapperClass)
    {
        return $this->builder()->wrapAs($wrapperClass);
    }
    
    /**
     * @param array $criteria
     * @param array $attributes
     * 
     * @return array|CollectionInterface
     * @throws PrimeException
     */
    #[ReadOperation]
    public function find(array $criteria, $attributes = null)
    {
        return $this->builder()->find($criteria, $attributes);
    }

    /**
     * @param array $criteria
     * @param array $attributes
     * 
     * @return E|null
     * @throws PrimeException
     */
    #[ReadOperation]
    public function findOne(array $criteria, $attributes = null)
    {
        return $this->builder()->findOne($criteria, $attributes);
    }

    /**
     * @see QueryInterface::where
     * 
     * @param string|array $relations
     * 
     * @return QueryInterface<ConnectionInterface, E>
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
     * @param E $entity
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

        /** @var string $original */
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
