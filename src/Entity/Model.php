<?php

namespace Bdf\Prime\Entity;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\PrimeSerializable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Relations\EntityRelation;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Serializer\Metadata\Builder\ClassMetadataBuilder;

/**
 * Model
 * 
 * Active record pattern
 *
 * @method static \Bdf\Prime\Collection\EntityCollection collection(array $entities = [])
 */
class Model extends PrimeSerializable implements EntityInterface, ImportableInterface
{
    /**
     * Exclude all properties from serialization
     *
     * @param ClassMetadataBuilder $builder
     * @throws PrimeException
     */
    public static function loadSerializerMetadata($builder)
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
    public function import(array $data)
    {
        if (empty($data)) {
            return;
        }

        self::locator()->hydrator($this)->hydrate($this, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function export(array $attributes = [])
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
     * @return EntityRepository|QueryInterface
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
    public function save()
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
    public function insert($ignore = false)
    {
        return static::repository()->insert($this, $ignore);
    }

    /**
     * Update this entity
     * 
     * @param array $attributes
     * 
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function update(array $attributes = null)
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
    public function replace()
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
    public function duplicate()
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
    public function delete()
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
    public function relation($relation)
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
    public function saveAll($relations)
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
    public function deleteAll($relations)
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
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function loaded(callable $listener, $once = true)
    {
        return static::repository()->loaded($listener, $once);
    }

    /**
     * Register pre save event
     * 
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function saving(callable $listener, $once = true)
    {
        return static::repository()->saving($listener, $once);
    }

    /**
     * Register post save event
     * 
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function saved(callable $listener, $once = true)
    {
        return static::repository()->saved($listener, $once);
    }

    /**
     * Register post insert event
     * 
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function inserting(callable $listener, $once = true)
    {
        return static::repository()->inserting($listener, $once);
    }

    /**
     * Register post insert event
     * 
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function inserted(callable $listener, $once = true)
    {
        return static::repository()->inserted($listener, $once);
    }

    /**
     * Register post update event
     * 
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function updating(callable $listener, $once = true)
    {
        return static::repository()->updating($listener, $once);
    }

    /**
     * Register post update event
     * 
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function updated(callable $listener, $once = true)
    {
        return static::repository()->updated($listener, $once);
    }

    /**
     * Register post delete event
     * 
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function deleting(callable $listener, $once = true)
    {
        return static::repository()->deleting($listener, $once);
    }

    /**
     * Register post delete event
     * 
     * @param callable $listener
     * @param bool     $once    Register on event once
     *
     * @return EntityRepository<static>
     */
    public static function deleted(callable $listener, $once = true)
    {
        return static::repository()->deleted($listener, $once);
    }
}
