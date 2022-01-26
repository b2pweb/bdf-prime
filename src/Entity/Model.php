<?php

namespace Bdf\Prime\Entity;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\PrimeSerializable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Relations\EntityRelation;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\RepositoryEventsSubscriberInterface;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Serializer\Metadata\Builder\ClassMetadataBuilder;

/**
 * Model
 * 
 * Active record pattern
 *
 * @psalm-type EntityCollection = \Bdf\Prime\Collection\EntityCollection<static>
 * @psalm-type EntityQuery = QueryInterface<\Bdf\Prime\Connection\ConnectionInterface, static>
 *
 * @method static \Bdf\Prime\Collection\EntityCollection collection(array $entities = [])
 * @psalm-method static EntityCollection collection(array<self> $entities = [])
 * @method static Criteria criteria(array $criteria = [])
 *
 * @method static static|null get(mixed $key)
 * @method static static getOrNew(mixed $key)
 * @method static static getOrFail(mixed $key)
 * @method static static|null findById(mixed $key)
 * @method static static|null findOne(array $criteria, ?array $attributes = null)
 *
 * @method static QueryInterface where(string|array|callable $column, mixed|null $operator = null, mixed $value = null)
 * @psalm-method static EntityQuery where(string|array|callable $column, mixed|null $operator = null, mixed $value = null)
 * @method static QueryInterface with(string|array $relations)
 * @psalm-method static EntityQuery with(string|array $relations)
 * @method static QueryInterface by(string $attribute, bool $combine = false)
 * @psalm-method static EntityQuery by(string $attribute, bool $combine = false)
 *
 * @method static int updateBy(array $attributes, array $criteria = [])
 * @method static int count(array $criteria = [], $attributes = null)
 * @method static bool exists(self $entity)
 * @method static static|null refresh(self $entity)
 */
class Model extends PrimeSerializable implements EntityInterface, ImportableInterface
{
    /**
     * Exclude all properties from serialization
     *
     * @param ClassMetadataBuilder $builder
     *
     * @throws PrimeException
     *
     * @return void
     */
    public static function loadSerializerMetadata(ClassMetadataBuilder $builder): void
    {
        if (!$repository = self::locator()->repository($builder->name())) {
            return;
        }

        $mapperInfo = $repository->mapper()->info();

        foreach ($mapperInfo->all() as $property) {
            $groups = ['all'];

            if ($property->isObject()) {
                /** @var \Bdf\Prime\Mapper\Info\ObjectPropertyInfo $property */
                if (!$property->belongsToRoot()) {
                    continue;
                }

                $type = $property->className();

                if ($property->isArray()) {
                    $wrapper = $property->wrapper();

                    // Set wrapper class, if can be resolved (a custom wrapper, with a closure, cannot be serialized)
                    if ($wrapper && is_string($wrapper)) {
                        $type = self::locator()->repository($type)->collectionFactory()->wrapperClass($wrapper).'<'.ltrim($type, '\\').'>';
                    } else {
                        $type .= '[]';
                    }
                }
            } else {
                /** @var \Bdf\Prime\Mapper\Info\PropertyInfo $property */
                $type = $property->phpType();

                if ($property->isPrimary()) {
                    $groups[] = 'identifier';
                }
            }

            $builder->add($property->name(), ltrim($type, '\\'), ['groups' => $groups]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function import(array $data): void
    {
        if (empty($data)) {
            return;
        }

        self::locator()->hydrator($this)->hydrate($this, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function export(array $attributes = []): array
    {
        return self::locator()->hydrator($this)->extract($this, $attributes);
    }

    /**
     * Get the associated repository
     * 
     * @return EntityRepository<static>
     */
    public static function repository()
    {
        return self::locator()->repository(static::class);
    }

    /**
     * Call method on this entity's repository
     * 
     * @param string $name
     * @param array $arguments
     * 
     * @return EntityRepository|QueryInterface|mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return static::repository()->$name(...$arguments);
    }

    /**
     * Save this entity
     * 
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function save(): int
    {
        return static::repository()->save($this);
    }

    /**
     * Insert this entity
     * 
     * @param bool $ignore
     * 
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function insert(bool $ignore = false): int
    {
        return static::repository()->insert($this, $ignore);
    }

    /**
     * Update this entity
     * 
     * @param list<string>|null $attributes
     * 
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function update(array $attributes = null): int
    {
        return static::repository()->update($this, $attributes);
    }

    /**
     * Replace this entity
     * 
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function replace(): int
    {
        return static::repository()->replace($this);
    }

    /**
     * Duplicate this entity
     * 
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function duplicate(): int
    {
        return static::repository()->duplicate($this);
    }

    /**
     * Delete this entity
     * 
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function delete(): int
    {
        return static::repository()->delete($this);
    }

    //---- relations

    /**
     * Load entity relations
     * If a relation is already loaded, the entity will be kept
     * You can force loading using reload()
     * 
     * @param string|array $relations
     * 
     * @return $this
     * @throws PrimeException
     *
     * @see Model::reload() For force loading
     */
    #[ReadOperation]
    public function load($relations)
    {
        static::repository()->loadRelations($this, $relations);

        return $this;
    }

    /**
     * For loading entity relations
     *
     * @param string|array $relations
     *
     * @return $this
     * @throws PrimeException
     */
    #[ReadOperation]
    public function reload($relations)
    {
        static::repository()->reloadRelations($this, $relations);

        return $this;
    }

    /**
     * Load entity relations
     * 
     * @param string $relation
     * 
     * @return EntityRelation<static, object>
     */
    public function relation(string $relation): EntityRelation
    {
        return static::repository()->onRelation($relation, $this);
    }

    /**
     * Save this entity and its relations
     * 
     * @param string|array $relations
     * 
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function saveAll($relations): int
    {
        return static::repository()->saveAll($this, $relations);
    }

    /**
     * Delete this entity and its relations
     * 
     * @param string|array $relations
     * 
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function deleteAll($relations): int
    {
        return static::repository()->deleteAll($this, $relations);
    }

    /**
     * Clear extra data from entities store into repository
     */
    public function __destruct()
    {
        // Active record not enabled : repository will not contains any information about entity
        if (!self::isActiveRecordEnabled()) {
            return;
        }

        // The entity may be a child of the real db entity, so no repository is related
        if ($repository = static::repository()) {
            $repository->free($this);
        }
    }

    //---- events

    /**
     * Register post load event
     * 
     * @param callable(static,RepositoryInterface<static>):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function loaded(callable $listener, bool $once = true): RepositoryEventsSubscriberInterface
    {
        return static::repository()->loaded($listener, $once);
    }

    /**
     * Register pre save event
     * 
     * @param callable(static,RepositoryInterface<static>,bool):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function saving(callable $listener, bool $once = true): RepositoryEventsSubscriberInterface
    {
        return static::repository()->saving($listener, $once);
    }

    /**
     * Register post save event
     * 
     * @param callable(static,RepositoryInterface<static>,int,bool):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function saved(callable $listener, bool $once = true): RepositoryEventsSubscriberInterface
    {
        return static::repository()->saved($listener, $once);
    }

    /**
     * Register post insert event
     * 
     * @param callable(static,RepositoryInterface<static>):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function inserting(callable $listener, bool $once = true): RepositoryEventsSubscriberInterface
    {
        return static::repository()->inserting($listener, $once);
    }

    /**
     * Register post insert event
     * 
     * @param callable(static,RepositoryInterface<static>,int):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function inserted(callable $listener, bool $once = true): RepositoryEventsSubscriberInterface
    {
        return static::repository()->inserted($listener, $once);
    }

    /**
     * Register post update event
     * 
     * @param callable(static,RepositoryInterface<static>,\ArrayObject<int,string>):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function updating(callable $listener, bool $once = true): RepositoryEventsSubscriberInterface
    {
        return static::repository()->updating($listener, $once);
    }

    /**
     * Register post update event
     * 
     * @param callable(static,RepositoryInterface<static>,int):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function updated(callable $listener, bool $once = true): RepositoryEventsSubscriberInterface
    {
        return static::repository()->updated($listener, $once);
    }

    /**
     * Register post delete event
     * 
     * @param callable(static,RepositoryInterface<static>):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function deleting(callable $listener, bool $once = true): RepositoryEventsSubscriberInterface
    {
        return static::repository()->deleting($listener, $once);
    }

    /**
     * Register post delete event
     * 
     * @param callable(static,RepositoryInterface<static>,int):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function deleted(callable $listener, bool $once = true): RepositoryEventsSubscriberInterface
    {
        return static::repository()->deleted($listener, $once);
    }
}
