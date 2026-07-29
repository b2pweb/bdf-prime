<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\Aggregatable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\Query;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Sharding\ShardingQuery;
use Doctrine\DBAL\Connection;
use Error;
use ReflectionClass;
use ReflectionException;

use function assert;
use function sprintf;

/**
 * EntityRelation
 *
 * Relation wrapper. Link an entity to an relation object
 *
 * @template E as object
 * @template R as object
 *
 * @api
 *
 * @psalm-method QueryInterface<\Bdf\Prime\Connection\ConnectionInterface, R> with(string|string[] $relations) Relations to load
 * @psalm-method QueryInterface<\Bdf\Prime\Connection\ConnectionInterface, R> by(string $attribute, bool $combine = false) Indexing entities by an attribute value. Use combine for multiple entities with same attribute value
 * @psalm-method QueryInterface<\Bdf\Prime\Connection\ConnectionInterface, R> where(string|iterable|callable $column, mixed|null $operator = null, mixed $value = null)
 * @psalm-method R|null findById(mixed|array $pk) Get one entity by its primary key or null if not found in repository
 * @psalm-method R findByIdOrFail(mixed|array $pk) Get one entity by its primary key or throws if not found in repository
 * @psalm-method R findByIdOrNew(mixed|array $pk) Get one entity by its primary key or instantiate a new one, using where clause criteria if not found in repository
 * @psalm-method R firstOrFail() Get the first result of the query, or throws an exception if no result
 * @psalm-method R firstOrNew(bool $useCriteriaAsDefault = true) Get the first result of the query, or create a new instance if no result. If $useCriteriaAsDefault is true, the where criteria will be used as default values for the new instance.
 *
 * @mixin ReadCommandInterface<\Bdf\Prime\Connection\ConnectionInterface, R>
 * @psalm-no-seal-methods
 */
final class EntityRelation
{
    /**
     * The entity owner of the relation
     *
     * @var E
     */
    private object $owner;

    /**
     * The relation
     *
     * @var RelationInterface<E, R>
     */
    private RelationInterface $relation;

    /**
     * EntityRelation constructor.
     *
     * @param E $owner     The relation owner
     * @param RelationInterface<E, R> $relation  The relation
     */
    public function __construct(object $owner, RelationInterface $relation)
    {
        $this->owner    = $owner;
        $this->relation = $relation;
    }

    /**
     * Create a new proxy instance for the relation entity
     *
     * This method must be called only for single entity relation (BelongsTo, HasOne),
     * otherwise it will only return the first entity of the relation.
     *
     * The relation query will be executed only when a property or method is accessed.
     * In any case, it will behave like a normal entity instance.
     *
     * @return R
     *
     * @throws PrimeException
     * @throws ReflectionException
     */
    public function proxy(): object
    {
        $r = new ReflectionClass($this->relation->relationRepository()->entityClass());
        return $r->newLazyProxy(fn () => $this->query()->firstOrFail());
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
     * @param R $entity The related entity data
     *
     * @return E Returns the owner entity instance
     */
    public function associate(object $entity): object
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
     * @return E Returns the owner entity instance
     */
    public function dissociate(): object
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
     * @return R Returns the related entity instance
     */
    public function create(array $data = []): object
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
     * @param R $related
     *
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function add(object $related): int
    {
        return $this->relation->add($this->owner, $related);
    }

    /**
     * Check whether the owner has a distant entity relation
     * Note: only works with BelongsToMany relation
     *
     * @param string|R $related
     *
     * @return boolean
     * @throws PrimeException
     */
    #[ReadOperation]
    public function has(mixed $related): bool
    {
        /** @var BelongsToMany $this->relation */
        return $this->relation->has($this->owner, $related);
    }

    /**
     * Attach a distant entity to an entity
     * Note: only works with BelongsToMany relation
     *
     * @param string|R[]|R $related
     *
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function attach(mixed $related): int
    {
        /** @var BelongsToMany<E, R> $this->relation */
        return $this->relation->attach($this->owner, $related);
    }

    /**
     * Detach a distant entity of an entity
     * Note: only works with BelongsToMany relation
     *
     * @param string|R[]|R $related
     *
     * @return int
     */
    public function detach(mixed $related): int
    {
        /** @var BelongsToMany<E, R> $this->relation */
        return $this->relation->detach($this->owner, $related);
    }

    /**
     * Gets the relation query builder
     * Note: You can use magic __call method to call query methods directly
     *
     * <code>
     * $entity->relation('foo')->where(['foo' => 'bar'])->get();
     *
     * // You can specify the required query type
     * $entity->relation('foo')->query(KeyValueQuery::class)->where('foo', 'bar'])->get();
     * </code>
     *
     * @param null|class-string<Q> $queryClass The query type to create. If null, the default query type will be used
     *
     * @return QueryInterface<\Bdf\Prime\Connection\ConnectionInterface, R>
     * @psalm-return (Q is null ? QueryInterface<ConnectionInterface, R> : (
     *                 Q is Query ? Query<ConnectionInterface&Connection, R> : (
     *                 Q is KeyValueQuery ? KeyValueQuery<ConnectionInterface, R> : (
     *                 Q is ShardingQuery ? ShardingQuery<R> : (
     *                 QueryInterface<ConnectionInterface, R>)))))
     *
     * @template Q as ReadCommandInterface
     */
    public function query(?string $queryClass = null): ReadCommandInterface
    {
        /** @psalm-suppress TooManyArguments */
        return $this->relation->link($this->owner, $queryClass);
    }

    /**
     * Count number of related entities matching the criteria
     *
     * Usage:
     * ```php
     * $entity->relation('relation')->count(); // Count all entities
     * $entity->relation('relation')->count(['status' => 'active']); // Count with criteria
     * $entity->relation('relation')->count(fn ($query) => $query->where('status', 'active')->where('age', '>', 18)); // Count with callback
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
        $query = $this->query()->where($criteria);
        assert($query instanceof Aggregatable);

        return $query->count($attributes);
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
    public function saveAll(string|array $relations = []): int
    {
        return $this->relation->saveAll($this->owner, (array)$relations);
    }

    /**
     * Remove the relation from an entity
     *
     * Note: This method can only works
     *
     * @param array|string $relations sub-relation names to delete
     *
     * @return int Number of deleted entities
     * @throws PrimeException When cannot delete entity
     */
    #[WriteOperation]
    public function deleteAll(string|array $relations = []): int
    {
        return $this->relation->deleteAll($this->owner, (array) $relations);
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
    public function __call(string $name, array $arguments): mixed
    {
        return $this->query()->$name(...$arguments);
    }
}
