<?php

namespace Bdf\Prime\Query;

use BadMethodCallException;
use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Events;
use Bdf\Prime\Exception\EntityNotFoundException;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\Closure\ClosureCompiler;
use Bdf\Prime\Query\Contract\Whereable;
use Bdf\Prime\Relations\Relation;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\RepositoryInterface;
use Closure;

/**
 * QueryRepositoryExtension
 *
 * @template E as object
 */
class QueryRepositoryExtension extends QueryCompatExtension
{
    /**
     * @var RepositoryInterface<E>
     */
    protected $repository;

    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var Mapper<E>
     */
    protected $mapper;

    /**
     * @var ClosureCompiler<E>|null
     */
    protected $closureCompiler;

    /**
     * Array of relations to associate on entities
     * Contains relations and subrelations. Can be load
     * only on select queries
     *
     * @var array
     */
    protected $withRelations = [];

    /**
     * Array of relations to discard
     *
     * @var array
     */
    protected $withoutRelations = [];

    /**
     * Collect entities by attribute
     *
     * @var array
     */
    protected $byOptions;


    /**
     * QueryRepositoryExtension constructor.
     *
     * @param RepositoryInterface<E> $repository
     * @param ClosureCompiler<E>|null $closureCompiler
     */
    public function __construct(RepositoryInterface $repository, ?ClosureCompiler $closureCompiler = null)
    {
        $this->repository = $repository;
        $this->metadata = $repository->metadata();
        $this->mapper = $repository->mapper();
        $this->closureCompiler = $closureCompiler;
    }

    /**
     * Gets associated repository
     *
     * @param ReadCommandInterface<ConnectionInterface, E> $query
     * @param null|string $name
     *
     * @return RepositoryInterface|null
     */
    public function repository(ReadCommandInterface $query, $name = null)
    {
        if ($name === null) {
            return $this->repository;
        }

        return $this->repository->repository($name);
    }

    /**
     * Get one entity by identifier
     *
     * @param ReadCommandInterface<ConnectionInterface, E>&Whereable $query
     * @param mixed         $id
     * @param null|string|array  $attributes
     *
     * @return E|null
     */
    public function get(ReadCommandInterface $query, $id, $attributes = null)
    {
        if (empty($id)) {
            return null;
        }

        if (!is_array($id)) {
            list($identifierName) = $this->metadata->primary['attributes'];
            $id = [$identifierName => $id];
        }

        return $query->where($id)->first($attributes);
    }

    /**
     * Get one entity or throws entity not found
     *
     * @param ReadCommandInterface<ConnectionInterface, E>&Whereable $query
     * @param mixed $id
     * @param null|string|array $attributes
     *
     * @return E
     *
     * @throws EntityNotFoundException  If entity is not found
     */
    public function getOrFail(ReadCommandInterface $query, $id, $attributes = null)
    {
        $entity = $this->get($query, $id, $attributes);

        if ($entity !== null) {
            return $entity;
        }

        throw new EntityNotFoundException('Cannot resolve entity identifier "'.implode('", "', (array)$id).'"');
    }

    /**
     * Get one entity or return a new one if not found in repository
     *
     * @param ReadCommandInterface<ConnectionInterface, E>&Whereable $query
     * @param mixed $id
     * @param null|string|array $attributes
     *
     * @return E
     */
    public function getOrNew(ReadCommandInterface $query, $id, $attributes = null)
    {
        $entity = $this->get($query, $id, $attributes);

        if ($entity !== null) {
            return $entity;
        }

        return $this->repository->entity();
    }

    /**
     * Filter entities by a predicate
     *
     * The predicate will be compiled to a where clause, instead of be called on each entity
     *
     * <code>
     * $query->filter(fn (User $user) => $user->enabled()); // WHERE enabled = 1
     * $query->filter(fn (User $user) => $user->enabled() && $user->age() > 18); // WHERE enabled = 1 AND age > 18
     * </code>
     *
     * @param ReadCommandInterface<ConnectionInterface, E> $query
     * @param Closure(E):bool $predicate The predicate. Must take the entity as parameter, and return a boolean.
     *
     * @return ReadCommandInterface<ConnectionInterface, E>
     */
    public function filter(ReadCommandInterface $query, Closure $predicate)
    {
        if (!$this->closureCompiler) {
            throw new BadMethodCallException('Closure filter is not enabled.');
        }

        if (!$query instanceof Whereable) {
            throw new BadMethodCallException('The query must implement ' . Whereable::class . ' to use filter with a closure.');
        }

        return $query->where($this->closureCompiler->compile($predicate));
    }

    /**
     * Relations to load.
     *
     * Relations with their sub relations
     * <code>
     * $query->with([
     *     'customer.packs',
     *     'permissions'
     * ]);
     * </code>
     *
     * Use char '#' for polymorphic sub relation
     * <code>
     * $query->with('target#customer.packs');
     * </code>
     *
     * @param ReadCommandInterface<ConnectionInterface, E> $query
     * @param string|array $relations
     *
     * @return ReadCommandInterface<ConnectionInterface, E>
     */
    public function with(ReadCommandInterface $query, $relations)
    {
        $this->withRelations = Relation::sanitizeRelations((array)$relations);

        return $query;
    }

    /**
     * Relations to discard
     *
     * @param ReadCommandInterface<ConnectionInterface, E> $query
     * @param string|array $relations
     *
     * @return ReadCommandInterface<ConnectionInterface, E>
     */
    public function without(ReadCommandInterface $query, $relations)
    {
        $this->withoutRelations = Relation::sanitizeWithoutRelations((array)$relations);

        return $query;
    }

    /**
     * Indexing entities by an attribute value
     * Use combine for multiple entities with same attribute value
     *
     * @param ReadCommandInterface<ConnectionInterface, E> $query
     * @param string  $attribute
     * @param boolean $combine
     *
     * @return ReadCommandInterface<ConnectionInterface, E>
     */
    public function by(ReadCommandInterface $query, $attribute, $combine = false)
    {
        $this->byOptions = [
            'attribute' => $attribute,
            'combine'   => $combine,
        ];

        return $query;
    }

    /**
     * Post processor for hydrating entities
     *
     * @param ResultSetInterface<array<string, mixed>> $data
     *
     * @return array
     * @throws PrimeException
     */
    public function processEntities(ResultSetInterface $data)
    {
        /** @var EntityRepository $repository */
        $repository = $this->repository;
        $hasLoadEvent = $repository->hasListeners(Events::POST_LOAD);

        // Save into local vars to ensure that value will not be changed during execution
        $withRelations = $this->withRelations;
        $withoutRelations = $this->withoutRelations;
        $byOptions = $this->byOptions;

        $entities = new EntityIndexer($this->mapper, $byOptions ? [$byOptions['attribute']] : []);

        // Force loading of eager relations
        if (!empty($this->metadata->eagerRelations)) {
            $withRelations = array_merge($this->metadata->eagerRelations, $withRelations);

            // Skip relation that should not be loaded.
            foreach ($withoutRelations as $relationName => $nestedRelations) {
                // Only a leaf concerns this query.
                if (empty($nestedRelations)) {
                    unset($withRelations[$relationName]);
                }
            }
        }

        foreach ($data as $result) {
            $entities->push($entity = $this->mapper->prepareFromRepository($result, $repository->connection()->platform()));

            if ($hasLoadEvent) {
                $repository->notify(Events::POST_LOAD, [$entity, $repository]);
            }
        }

        foreach ($withRelations as $relationName => $relationInfos) {
            $repository->relation($relationName)->load(
                $entities,
                $relationInfos['relations'],
                $relationInfos['constraints'],
                $withoutRelations[$relationName] ?? []
            );
        }

        switch (true) {
            case $byOptions === null:
                return $entities->all();

            case $byOptions['combine']:
                return $entities->by($byOptions['attribute']);

            default:
                return $entities->byOverride($byOptions['attribute']);
        }
    }

    /**
     * Scope call
     * run a scope defined in repository
     *
     * @param string $name          Scope name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        /** @var EntityRepository $this->repository */
        $scopes = $this->repository->scopes();

        if (!isset($scopes[$name])) {
            throw new BadMethodCallException('Scope "' . get_class($this->mapper) . '::' . $name . '" not found');
        }

        return $scopes[$name](...$arguments);
    }

    /**
     * Configure the query
     *
     * @param ReadCommandInterface $query
     *
     * @return void
     */
    public function apply(ReadCommandInterface $query): void
    {
        $query->setExtension($this);
        $query->post([$this, 'processEntities'], false);
    }
}
