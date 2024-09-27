<?php

namespace Bdf\Prime\Collection;

use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Entity\ImportableInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Relations\Relation;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\RepositoryEventsSubscriberInterface;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\Repository\Write\BufferedWriter;
use IteratorAggregate;

/**
 * Collection of entities
 *
 * Can process optimized bulk operations, like delete, update, or load
 * Provide helpers method for bulk entities handling, like save, refresh, link, query
 *
 * <code>
 * $collection = Entity::collection(); // Create the collection
 * $collection->push($entity); // Add an entity
 * $collection->save(); // Save all entities
 * $collection->update(['value' => 42]); // Update attribute "value" to 42
 * </code>
 *
 * @todo Optimize bulk insert query
 *
 * @template E as object
 *
 * @implements CollectionInterface<E>
 * @implements IteratorAggregate<array-key, E>
 */
class EntityCollection implements IteratorAggregate, CollectionInterface, ImportableInterface
{
    /**
     * @var RepositoryInterface<E>
     */
    private $repository;

    /**
     * @var CollectionInterface<E>
     */
    private $storage;


    /**
     * RelationCollection constructor.
     *
     * @param RepositoryInterface<E> $repository The entity repository
     * @param CollectionInterface<E>|E[]|null $storage
     *
     * @internal Should not be created manually
     */
    public function __construct(RepositoryInterface $repository, $storage = null)
    {
        if (!$storage instanceof CollectionInterface) {
            /** @psalm-suppress InvalidArgument */
            $storage = new ArrayCollection($storage);
        }

        $this->repository = $repository;
        $this->storage = $storage;
    }

    /**
     * Load relations on entities
     *
     * @param array|string $relations The relations to load. Can be a string for load only one relation
     *
     * @return $this
     * @throws PrimeException
     *
     * @todo Faut-il utiliser loadIfNotLoaded ?
     */
    #[ReadOperation]
    public function load($relations)
    {
        foreach (Relation::sanitizeRelations((array)$relations) as $relationName => $meta) {
            $this->repository->relation($relationName)->load(
                EntityIndexer::fromArray($this->repository->mapper(), $this->storage->all()),
                $meta['relations'],
                $meta['constraints']
            );
        }

        return $this;
    }

    /**
     * Link a query on each elements
     *
     * This method will configure query like :
     * SELECT * FROM relation WHERE relation.fk IN (entity1.key, entity2.key, ...)
     *
     * <code>
     * // Perform query on customer.customerPack.pack
     * $customer->relation('packs')
     *     ->wrapAs('collection')
     *     ->all()
     *     ->link('pack')
     *     ->where(...)
     *     ->all()
     * ;
     * </code>
     *
     * @param string $relation The relation name
     *
     * @return QueryInterface
     * @fixme Works with Polymorph
     */
    public function link($relation)
    {
        return $this->repository
            ->relation($relation)
            ->link($this->all())
        ;
    }

    /**
     * Get the query, related to all entities
     *
     * This method will configure query like :
     * SELECT * FROM entity WHERE pk IN (entity1.pk, entity2.pk, ...)
     *
     * @return QueryInterface<ConnectionInterface, E>
     */
    public function query()
    {
        return $this->repository->queries()->entities($this->all());
    }

    /**
     * Delete all entities in the collection
     *
     * /!\ The collection will not be cleared. The deleted entities can still be used
     *
     * @return $this
     * @throws PrimeException
     */
    #[WriteOperation]
    public function delete()
    {
        $this->repository->transaction(function (RepositoryInterface $repository) {
            /** @var RepositoryInterface&RepositoryEventsSubscriberInterface $repository */
            $writer = new BufferedWriter($repository);

            foreach ($this as $entity) {
                $writer->delete($entity);
            }

            $writer->flush();
        });

        return $this;
    }

    /**
     * Save all entities in the collection
     *
     * @return $this
     * @throws PrimeException
     */
    #[WriteOperation]
    public function save()
    {
        $this->repository->transaction(function (RepositoryInterface $repository) {
            foreach ($this as $entity) {
                $repository->save($entity);
            }
        });

        return $this;
    }

    /**
     * Save entities and its relations
     * /!\ Not optimized
     *
     * @param string|array $relations The relations names
     *
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function saveAll($relations)
    {
        $relations = Relation::sanitizeRelations((array)$relations);

        return $this->repository->transaction(function (RepositoryInterface $repository) use ($relations) {
            $nb = 0;

            foreach ($this as $entity) {
                $nb += $repository->save($entity);

                foreach ($relations as $relationName => $info) {
                    $nb += $repository->relation($relationName)->saveAll($entity, $info['relations']);
                }
            }

            return $nb;
        });
    }

    /**
     * Delete entities and its relations
     * /!\ Not optimized
     *
     * @param string|array $relations The relations names
     *
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function deleteAll($relations)
    {
        $relations = Relation::sanitizeRelations((array)$relations);

        return $this->repository->transaction(function (RepositoryInterface $repository) use ($relations) {
            $nb = 0;

            foreach ($this as $entity) {
                $nb += $repository->delete($entity);

                foreach ($relations as $relationName => $info) {
                    $nb += $repository->relation($relationName)->deleteAll($entity, $info['relations']);
                }
            }

            return $nb;
        });
    }

    /**
     * Perform an update on all entities
     * Update in database AND entities attributes
     *
     * Example:
     * <code>
     * $tasks = Task::wrapAs('collection')->limit(10)->all();
     * // $tasks = [
     * //    new Task(['id' => 1, 'processing' => false]),
     * //    new Task(['id' => 2, 'processing' => false]),
     * //    new Task(['id' => 3, 'processing' => false]),
     * // ];
     *
     * $tasks->update(['proccessing' => true]);
     * // $tasks = [
     * //    new Task(['id' => 1, 'processing' => true]),
     * //    new Task(['id' => 2, 'processing' => true]),
     * //    new Task(['id' => 3, 'processing' => true]),
     * // ];
     * </code>
     *
     * @param array $data Data to set (in form [attribute] => [value])
     *
     * @return $this
     * @throws PrimeException
     */
    #[WriteOperation]
    public function update(array $data)
    {
        foreach ($this as $entity) {
            $entity->import($data);
        }

        $this->query()->update($data);

        return $this;
    }

    /**
     * Refresh all entities into the collection
     *
     * This method is equivalent to re-select all entities
     *
     * /!\ Do not refresh each entities, but the entire collection. Do not store references if you want to refresh the collection
     *
     * @return $this
     * @throws PrimeException
     */
    #[ReadOperation]
    public function refresh()
    {
        $this->pushAll($this->query()->all());

        return $this;
    }

    /**
     * Get the related repository
     *
     * @return RepositoryInterface<E>
     */
    public function repository()
    {
        return $this->repository;
    }

    /**
     * {@inheritdoc}
     *
     * Replace all entities of the collection with imported data
     * Entities will be instantiated with given data
     *
     * <code>
     * Person::collection()->import([
     *     ['name' => 'John'],
     *     ['name' => 'Mark'],
     * ]);
     * // [ new Person(['name' => 'John']),
     * //   new Person(['name' => 'Mark']) ]
     * </code>
     */
    public function import(array $data): void
    {
        $entities = [];

        foreach ($data as $value) {
            if (is_array($value)) {
                $entities[] = $this->repository->entity($value);
            } else {
                $entities[] = $value;
            }
        }

        $this->pushAll($entities);
    }

    /**
     * {@inheritdoc}
     */
    public function export(array $attributes = []): array
    {
        $data = [];

        foreach ($this as $entity) {
            $data[] = $entity->export($attributes);
        }

        return $data;
    }

    //===================//
    // Delegated methods //
    //===================//

    /**
     * {@inheritdoc}
     */
    public function pushAll(array $items)
    {
        $this->storage->pushAll($items);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function push($item)
    {
        $this->storage->push($item);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function put($key, $item)
    {
        $this->storage->put($key, $item);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->storage->all();
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        return $this->storage->get($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return $this->storage->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $this->storage->remove($key);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->storage->clear();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return $this->storage->keys();
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return $this->storage->isEmpty();
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidArgument
     */
    public function map($callback)
    {
        // @fixme does return static make sense ?
        /** @psalm-suppress InvalidReturnStatement */
        return new static($this->repository, $this->storage->map($callback));
    }

    /**
     * {@inheritdoc}
     */
    public function filter($callback = null)
    {
        return new static($this->repository, $this->storage->filter($callback));
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy($groupBy, $mode = self::GROUPBY)
    {
        return new static($this->repository, $this->storage->groupBy($groupBy, $mode));
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element)
    {
        return $this->storage->contains($element);
    }

    /**
     * {@inheritdoc}
     */
    public function indexOf($value, $strict = false)
    {
        return $this->storage->indexOf($value, $strict);
    }

    /**
     * {@inheritdoc}
     */
    public function merge($items)
    {
        return new static($this->repository, $this->storage->merge($items));
    }

    /**
     * {@inheritdoc}
     */
    public function sort(callable $callback = null)
    {
        return new static($this->repository, $this->storage->sort($callback));
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->storage->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return $this->storage->offsetExists($offset);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->storage->offsetGet($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->storage->offsetSet($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        $this->storage->offsetUnset($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->storage->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->storage->all());
    }
}
