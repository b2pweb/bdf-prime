<?php

namespace Bdf\Prime\Repository;

use BadMethodCallException;
use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Collection\Indexer\SingleEntityIndexer;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\TransactionManagerInterface;
use Bdf\Prime\Entity\Criteria;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\Contract\Aggregatable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Bdf\Prime\Query\Pagination\PaginatorInterface;
use Bdf\Prime\Query\Query;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\QueryRepositoryExtension;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Relations\EntityRelation;
use Bdf\Prime\Relations\Relation;
use Bdf\Prime\Relations\RelationInterface;
use Bdf\Prime\Repository\Event\AfterDelete;
use Bdf\Prime\Repository\Event\AfterInsert;
use Bdf\Prime\Repository\Event\AfterLoad;
use Bdf\Prime\Repository\Event\AfterSave;
use Bdf\Prime\Repository\Event\AfterUpdate;
use Bdf\Prime\Repository\Event\BeforeDelete;
use Bdf\Prime\Repository\Event\BeforeInsert;
use Bdf\Prime\Repository\Event\BeforeSave;
use Bdf\Prime\Repository\Event\BeforeUpdate;
use Bdf\Prime\Repository\Event\EventNotifierTrait;
use Bdf\Prime\Repository\Write\Writer;
use Bdf\Prime\Repository\Write\WriterInterface;
use Bdf\Prime\Schema\NullStructureUpgrader;
use Bdf\Prime\Schema\RepositoryUpgrader;
use Bdf\Prime\Schema\StructureUpgraderInterface;
use Bdf\Prime\ServiceLocator;
use Bdf\Prime\Sharding\ShardingQuery;
use Closure;
use Doctrine\DBAL\Connection;
use Exception;

use function assert;

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
 * @mixin QueryInterface<ConnectionInterface, E>
 *
 * @method E|null findById(mixed $key)
 * @method E findByIdOrFail(mixed $key)
 * @method E findByIdOrNew(mixed $key)
 * @method QueryInterface<ConnectionInterface, E> filter(Closure $filter)
 *
 * @psalm-no-seal-methods
 */
class EntityRepository implements RepositoryInterface, RepositoryEventsSubscriberInterface
{
    use EventNotifierTrait;

    /**
     * @var Mapper<E>
     */
    protected Mapper $mapper;
    protected ServiceLocator $serviceLocator;

    /**
     * Query result cache
     */
    protected ?CacheInterface $resultCache;

    /**
     * Disable the global constraints for one query
     *
     * @var bool
     */
    protected bool $withoutConstraints = false;

    /**
     * Cache of relation instance
     *
     * @var array<string, RelationInterface<E, object>>
     */
    protected array $relations = [];

    /**
     * The collection factory
     *
     * @var CollectionFactory
     */
    protected CollectionFactory $collectionFactory;

    /**
     * @var RepositoryQueryFactory<E>
     */
    protected RepositoryQueryFactory $queries;

    /**
     * @var WriterInterface<E>
     */
    protected WriterInterface $writer;
    protected ?ConnectionInterface $connection = null;

    /**
     * @var Closure(ConnectionInterface):void
     */
    private Closure $onCloseListener;


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
        $this->onCloseListener = fn (ConnectionInterface $connection) => $this->reset();

        $this->collectionFactory = CollectionFactory::forRepository($this);
        $this->queries = new RepositoryQueryFactory($this, $cache, $serviceLocator->mappers()->getMetadataCache());
        $this->writer = new Writer($this, $serviceLocator);

        $this->mapper->events($this);
    }

    /**
     * {@inheritdoc}
     */
    public function repository(string|object $entity): ?RepositoryInterface
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
        return $this->mapper->criteria($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function entity(array $data = []): object
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
    public function hydrateOne(object $entity, string $property, mixed $value): void
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
    public function extractOne(object $entity, string $property): mixed
    {
        return $this->mapper->extractOne($entity, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function connection(): ConnectionInterface
    {
        if ($this->connection === null) {
            //Repository query factory load the connection on its constructor. Use lazy to let the connection being loaded as late as possible.
            $this->connection = $this->serviceLocator->connection($this->mapper->metadata()->connection);
            $this->connection->addConnectionClosedListener($this->onCloseListener);
        }

        return $this->connection;
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
    public function on(string $connection, ?Closure $work = null): mixed
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
    public function transaction(callable $work): mixed
    {
        $connection = $this->connection();

        if (!$connection instanceof TransactionManagerInterface) {
            throw new BadMethodCallException('Transactions are not supported by the connection '.$connection->getName());
        }

        return $connection->inTransaction(fn () => $work($this));
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
    public function loadRelations(object $entity, string|array $relations): void
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
    public function reloadRelations(object $entity, string|array $relations): void
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
     * @param class-string<R>|string $relationClass The relation class name, or the relation name
     * @param string|null $relationName The relation name if the there is multiple relation on the same class
     * @param E $entity
     *
     * @return EntityRelation<E, R>
     * @template R as object
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    public function onRelation(string $relationClass, object $entity, ?string $relationName = null): EntityRelation
    {
        return new EntityRelation($entity, $this->relation($relationClass, $relationName));
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function relation(string $relationClass, ?string $relationName = null): RelationInterface
    {
        if ($relation = $this->relations[$relationName ?? $relationClass] ?? null) {
            return $relation;
        }

        $metadata = $this->mapper->relation($relationClass, $relationName);
        $relationName = $metadata['name'];

        return ($this->relations[$relationName] ??= Relation::make($this, $relationName, $metadata));
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function saveAll(object $entity, string|array $relations): int
    {
        $relations = Relation::sanitizeRelations((array)$relations);

        return $this->transaction(function () use ($entity, $relations) {
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
    public function deleteAll(object $entity, string|array $relations): int
    {
        $relations = Relation::sanitizeRelations((array)$relations);

        return $this->transaction(function () use ($entity, $relations) {
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
    public function constraints(?string $context = null): array
    {
        if ($this->withoutConstraints === true) {
            $this->withoutConstraints = false;
            return [];
        }

        $constraints = $this->metadata()->constraints;

        if ($context) {
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
    public function withoutConstraints(): static
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
    public function builder(): QueryInterface
    {
        return $this->queries->builder();
    }

    /**
     * Get query builder of the given type
     *
     * @param null|class-string<Q> $queryClass The query type to create. If null, the default query type will be used
     *
     * @return QueryInterface<ConnectionInterface, E>
     * @psalm-return (Q is null ? QueryInterface<ConnectionInterface, E> : (
     *                Q is Query ? Query<ConnectionInterface&Connection, E> : (
     *                Q is KeyValueQuery ? KeyValueQuery<ConnectionInterface, E> : (
     *                Q is ShardingQuery ? ShardingQuery<E> : (
     *                QueryInterface<ConnectionInterface, E>)))))
     *
     * @template Q as ReadCommandInterface
     */
    public function query(?string $queryClass = null): ReadCommandInterface
    {
        return $queryClass ? $this->queries->make($queryClass) : $this->queries->builder();
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
     * Count number of entities matching the criteria
     *
     * Usage:
     * ```php
     * MyEntity::repository()->count(); // Count all entities
     * MyEntity::repository()->count(['status' => 'active']); // Count with criteria
     * MyEntity::repository()->count(fn ($query) => $query->where('status', 'active')->where('age', '>', 18)); // Count with callback
     * ```
     *
     * @param iterable<string,mixed>|callable(QueryInterface):void $criteria The filtering criteria. If not set, will count all entities of the repository
     * @param string|array|null $attributes The attribute(s) to count. If null, will count all (COUNT(*))
     *
     * @return int
     * @throws PrimeException
     */
    #[ReadOperation]
    public function count(iterable|callable $criteria = [], string|array|null $attributes = null): int
    {
        $query = $this->queries->builder()->where($criteria);
        assert($query instanceof Aggregatable);

        return $query->count($attributes);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function exists(object $entity): bool
    {
        return $this->queries->countKeyValue($this->mapper()->primaryCriteria($entity)) > 0;
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function refresh(object $entity, array $criteria = []): ?object
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
    public function isNew(object $entity): ?bool
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
    public function save(object $entity): int
    {
        $isNew = $this->isNew($entity);

        if ($this->notify(new BeforeSave($entity, $this, $isNew)) === false) {
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

        $this->notify(new AfterSave($entity, $this, $count, $isNew));

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
    public function replace(object $entity): int
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
    public function duplicate(object $entity): int
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
    public function insert(object $entity, bool $ignore = false): int
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
    public function insertIgnore(object $entity): int
    {
        return $this->insert($entity, true);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function update(object $entity, ?array $attributes = null): int
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
    public function delete(object $entity): int
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
    public function schema(bool $force = false): StructureUpgraderInterface
    {
        $ignore = !$this->mapper->hasSchemaManager() || ($this->connection()->getParameters()['ignore'] ?? false);

        if ($ignore && !$force) {
            return new NullStructureUpgrader();
        }

        return new RepositoryUpgrader($this->serviceLocator, $this->mapper->metadata());
    }

    /**
     * Gets custom filters
     *
     * @return array
     */
    public function filters(): array
    {
        return $this->mapper->filters();
    }

    /**
     * Repository extensions
     *
     * @return array<string, callable(\Bdf\Prime\Query\QueryInterface,mixed...):mixed>
     */
    public function scopes(): array
    {
        return $this->mapper->scopes();
    }

    //----- events

    /**
     * {@inheritdoc}
     */
    public function loaded(callable $listener, bool $once = false): static
    {
        $eventName = AfterLoad::class;

        if ($once) {
            $this->once($eventName, $listener);
        } else {
            $this->listen($eventName, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function saving(callable $listener, bool $once = false): static
    {
        $eventName = BeforeSave::class;

        if ($once) {
            $this->once($eventName, $listener);
        } else {
            $this->listen($eventName, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function saved(callable $listener, bool $once = false): static
    {
        $eventName = AfterSave::class;

        if ($once) {
            $this->once($eventName, $listener);
        } else {
            $this->listen($eventName, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function inserting(callable $listener, bool $once = false): static
    {
        $eventName = BeforeInsert::class;

        if ($once) {
            $this->once($eventName, $listener);
        } else {
            $this->listen($eventName, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function inserted(callable $listener, bool $once = false): static
    {
        $eventName = AfterInsert::class;

        if ($once) {
            $this->once($eventName, $listener);
        } else {
            $this->listen($eventName, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function updating(callable $listener, bool $once = false): static
    {
        $eventName = BeforeUpdate::class;

        if ($once) {
            $this->once($eventName, $listener);
        } else {
            $this->listen($eventName, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function updated(callable $listener, bool $once = false): static
    {
        $eventName = AfterUpdate::class;

        if ($once) {
            $this->once($eventName, $listener);
        } else {
            $this->listen($eventName, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deleting(callable $listener, bool $once = false): static
    {
        $eventName = BeforeDelete::class;

        if ($once) {
            $this->once($eventName, $listener);
        } else {
            $this->listen($eventName, $listener);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deleted(callable $listener, bool $once = false): static
    {
        $eventName = AfterDelete::class;

        if ($once) {
            $this->once($eventName, $listener);
        } else {
            $this->listen($eventName, $listener);
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
     * @return int|QueryInterface|array|E
     */
    public function __call(string $name, array $arguments): mixed
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
    public function with(string|array $relations): QueryInterface
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
    public function without(string|array $relations): QueryInterface
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
    public function by(string|array $attribute, bool $combine = false): QueryInterface
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
    public function wrapAs(string $wrapperClass): QueryInterface
    {
        return $this->builder()->wrapAs($wrapperClass);
    }

    /**
     * @param array $criteria
     * @param array $attributes
     *
     * @return E[]|CollectionInterface<E>
     * @throws PrimeException
     */
    #[ReadOperation]
    public function find(array $criteria, string|array|null $attributes = null): array|CollectionInterface|PaginatorInterface
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
    public function findOne(array $criteria, ?array $attributes = null): ?object
    {
        return $this->builder()->findOne($criteria, $attributes);
    }

    /**
     * @see QueryInterface::where
     *
     * @param string|iterable<string,mixed>|callable(static):void|ExpressionInterface $column The restriction predicates.
     * @param string|mixed|null $operator The comparison operator, or the value is you want to use "=" operator
     * @param mixed $value
     *
     * @return QueryInterface<ConnectionInterface, E>
     */
    public function where(string|iterable|callable|ExpressionInterface $column, mixed $operator = null, mixed $value = null): QueryInterface
    {
        return $this->builder()->where($column, $operator, $value);
    }

    /**
     * Clear dependencies for break cyclic references
     * After this call, the repository will be unusable
     *
     * @internal
     *
     * @return void
     */
    public function destroy(): void
    {
        if ($this->connection !== null) {
            $this->connection->removeConnectionClosedListener($this->onCloseListener);
            $this->connection = null;
        }

        unset($this->serviceLocator);
        unset($this->queries);
        unset($this->writer);
        $this->relations = [];
        unset($this->collectionFactory);

        $this->mapper->destroy();
        unset($this->mapper);

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
    private function changeActiveConnection(string $connectionName): string
    {
        $this->reset();

        /** @var string $original */
        $original = $this->mapper->metadata()->connection;
        $this->mapper->metadata()->connection = $connectionName;

        return $original;
    }

    /**
     * Reset the inner queries and the connection.
     * Use for invalidate prepared queries, or when connection changed
     *
     * @return void
     */
    private function reset(): void
    {
        if ($this->connection !== null) {
            $this->connection->removeConnectionClosedListener($this->onCloseListener);
            $this->connection = null;
        }

        // Reset queries
        $this->queries = new RepositoryQueryFactory($this, $this->resultCache, $this->serviceLocator->mappers()->getMetadataCache());
        $this->writer = new Writer($this, $this->serviceLocator);
        $this->relations = []; // Relation may contains inner query : it must be reseted

        if ($this->resultCache) {
            $this->resultCache->clear();
        }
    }
}
