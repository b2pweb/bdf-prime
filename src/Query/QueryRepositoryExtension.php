<?php

namespace Bdf\Prime\Query;

use BadMethodCallException;
use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Events;
use Bdf\Prime\Exception\EntityNotFoundException;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Relations\Relation;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * QueryRepositoryExtension
 */
class QueryRepositoryExtension extends QueryCompatExtension
{
    /**
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var Mapper
     */
    protected $mapper;

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
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->metadata = $repository->metadata();
        $this->mapper = $repository->mapper();
    }

    /**
     * Gets associated repository
     *
     * @param ReadCommandInterface $query
     * @param null|string $name
     *
     * @return RepositoryInterface
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
     * @param ReadCommandInterface $query
     * @param mixed         $id
     * @param null|string|array  $attributes
     *
     * @return object|null
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
     * @param ReadCommandInterface $query
     * @param mixed         $id
     * @param null|string|array  $attributes
     *
     * @return object
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
     * @param ReadCommandInterface $query
     * @param mixed         $id
     * @param null|string|array  $attributes
     *
     * @return object
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
     * @param ReadCommandInterface $query
     * @param string|array $relations
     *
     * @return ReadCommandInterface
     */
    public function with(ReadCommandInterface $query, $relations)
    {
        $this->withRelations = Relation::sanitizeRelations((array)$relations);

        return $query;
    }

    /**
     * Relations to discard
     *
     * @param ReadCommandInterface $query
     * @param string|array $relations
     *
     * @return ReadCommandInterface
     */
    public function without(ReadCommandInterface $query, $relations)
    {
        $this->withoutRelations = Relation::sanitizeWithoutRelations((array)$relations);

        return $query;
    }

    /**
     * Collect entities by attribute
     *
     * @param ReadCommandInterface $query
     * @param string  $attribute
     * @param boolean $combine
     *
     * @return ReadCommandInterface
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
     * @param array $data
     *
     * @return array
     * @throws PrimeException
     */
    public function processEntities(array $data)
    {
        $hasLoadEvent  = $this->repository->hasListeners(Events::POST_LOAD);

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
            $entities->push($entity = $this->mapper->prepareFromRepository($result, $this->repository->connection()->platform()));

            if ($hasLoadEvent === true) {
                $this->repository->notify(Events::POST_LOAD, [$entity, $this->repository]);
            }
        }

        foreach ($withRelations as $relationName => $relationInfos) {
            $this->repository->relation($relationName)->load(
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
        $scopes = $this->repository->scopes();

        if (!isset($scopes[$name])) {
            throw new BadMethodCallException('Method "' . get_class($this->repository) . '::' . $name . '" not found');
        }

        return $scopes[$name](...$arguments);
    }

    /**
     * Configure the query
     *
     * @param ReadCommandInterface $query
     */
    public function apply(ReadCommandInterface $query)
    {
        $query->setExtension($this);
        $query->post([$this, 'processEntities'], false);
    }
}
