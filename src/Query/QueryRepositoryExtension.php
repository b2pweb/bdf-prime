<?php

namespace Bdf\Prime\Query;

use BadMethodCallException;
use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Events;
use Bdf\Prime\Exception\EntityNotFoundException;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Exception\QueryBuildingException;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\Closure\ClosureCompiler;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use Bdf\Prime\Query\Contract\Whereable;
use Bdf\Prime\Relations\Relation;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\RepositoryInterface;
use Closure;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

use function array_diff;
use function array_keys;
use function count;
use function is_array;

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
     * @deprecated Since 2.1. Use {@see findById()} instead.
     */
    public function get(ReadCommandInterface $query, $id, $attributes = null)
    {
        @trigger_error('Query::get()/getOrFail()/getOrNew() is deprecated since 2.1. Use findById() instead.', E_USER_DEPRECATED);

        if (empty($id)) {
            return null;
        }

        if (!is_array($id)) {
            list($identifierName) = $this->metadata->primary['attributes'];
            $id = [$identifierName => $id];
        } else {
            foreach ($id as $key => $value) {
                if (is_int($key)) {
                    throw new \InvalidArgumentException('Raw SQL expressions are not allowed in get() method');
                }
            }
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
     * @deprecated Since 2.1. Use {@see findByIdOrFail()} instead.
     */
    public function getOrFail(ReadCommandInterface $query, $id, $attributes = null)
    {
        /** @psalm-suppress DeprecatedMethod */
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
     * @deprecated Since 2.1. Use {@see findByIdOrNew()} instead.
     */
    public function getOrNew(ReadCommandInterface $query, $id, $attributes = null)
    {
        /** @psalm-suppress DeprecatedMethod */
        $entity = $this->get($query, $id, $attributes);

        if ($entity !== null) {
            return $entity;
        }

        return $this->repository->entity();
    }

    /**
     * Find entity by its primary key
     * In case of composite primary key, the primary key can be resolved by previous where() call
     *
     * Note: If criterion which is not part of the primary key is passed, or if the primary key is not complete, the query will throw an {@see QueryBuildingException}.
     *
     * <code>
     * $queries->findById(2);
     * $queries->findById(['key1' => 1, 'key2' => 5]);
     * $queries->where('key1', 1)->findById(5); // Same as above: the composite key is completed by previous where() call
     * </code>
     *
     * @param ReadCommandInterface<ConnectionInterface, E>&Whereable $query
     * @param mixed|array<string, mixed> $id The entity PK. Use an array for composite PK
     *
     * @return E|null The entity or null if not found
     * @throws PrimeException When query fail
     */
    public function findById(ReadCommandInterface $query, $id)
    {
        $pkAttributes = $this->metadata->primary['attributes'];
        $criteria = null;

        // Scalar id is used, so resolve the primary key attribute name
        if (!is_array($id)) {
            if (count($pkAttributes) === 1) {
                // Single primary key
                $id = [$pkAttributes[0] => $id];
            } else {
                // Composite primary key : resolve missing primary key attributes
                $criteria = $this->toCriteria($query) ?? [];

                foreach ($pkAttributes as $key) {
                    if (!isset($criteria[$key])) {
                        $id = [$key => $id];
                        break;
                    }
                }

                if (!is_array($id)) {
                    throw new QueryBuildingException('Ambiguous findById() call : All primary key attributes are already defined on query, so missing part of the primary key cannot be resolved. Use an array as parameter instead to explicitly define the primary key attribute name.');
                }
            }
        }

        $keys = array_keys($id);

        // Some criteria are not part of the primary key
        if ($extraKeys = array_diff($keys, $pkAttributes)) {
            throw new QueryBuildingException('Only primary keys must be passed to findById(). Unexpected keys : ' . implode(', ', $extraKeys));
        }

        $missingPk = array_diff($pkAttributes, $keys);

        if ($missingPk) {
            // Some primary key attributes are missing
            // so check if they are defined in the query on previous where() call
            $criteria ??= $this->toCriteria($query) ?? [];

            foreach ($missingPk as $i => $key) {
                if (isset($criteria[$key])) {
                    unset($missingPk[$i]);
                }
            }

            if ($missingPk) {
                throw new QueryBuildingException('Only primary keys must be passed to findById(). Missing keys : ' . implode(', ', $missingPk));
            }
        }

        return $query->where($id)->first();
    }

    /**
     * Find entity by its primary key, or throws exception if not found in repository
     * In case of composite primary key, the primary key can be resolved by previous where() call
     *
     * Note: If criterion which is not part of the primary key is passed, or if the primary key is not complete, the query will throw an {@see QueryBuildingException}.
     *
     * @param ReadCommandInterface<ConnectionInterface, E>&Whereable $query
     * @param mixed|array<string, mixed> $id The entity PK. Use an array for composite PK
     *
     * @return E
     *
     * @throws EntityNotFoundException  If entity is not found
     * @throws PrimeException           When query fail
     */
    public function findByIdOrFail(ReadCommandInterface $query, $id)
    {
        $entity = $this->findById($query, $id);

        if ($entity !== null) {
            return $entity;
        }

        throw new EntityNotFoundException('Cannot resolve entity identifier "'.implode('", "', (array)$id).'"');
    }

    /**
     * Find entity by its primary key, or throws exception if not found in repository
     * In case of composite primary key, the primary key can be resolved by previous where() call
     *
     * Note: If criterion which is not part of the primary key is passed, or if the primary key is not complete, the query will throw an {@see QueryBuildingException}.
     *
     * @param ReadCommandInterface<ConnectionInterface, E>&Whereable $query
     * @param mixed|array<string, mixed> $id The entity PK. Use an array for composite PK
     *
     * @return E
     *
     * @throws EntityNotFoundException  If entity is not found
     * @throws PrimeException           When query fail
     */
    public function findByIdOrNew(ReadCommandInterface $query, $id)
    {
        $entity = $this->findById($query, $id);

        if ($entity !== null) {
            return $entity;
        }

        return $this->repository->entity($this->toCriteria($query) ?? []);
    }

    /**
     * Execute the query and return the first result, or throw {@see EntityNotFoundException} if no result
     *
     * @param ReadCommandInterface<ConnectionInterface, E> $query
     *
     * @return E
     */
    public function firstOrFail(ReadCommandInterface $query)
    {
        $entity = $query->first();

        if ($entity !== null) {
            return $entity;
        }

        throw new EntityNotFoundException('Cannot resolve entity');
    }

    /**
     * Execute the query and return the first result, or instantiate a new entity if no result
     *
     * @param ReadCommandInterface<ConnectionInterface, E> $query
     * @param bool $useCriteriaAsDefault If true, the criteria of the query will be used as default attributes
     *
     * @return E
     */
    public function firstOrNew(ReadCommandInterface $query, bool $useCriteriaAsDefault = true)
    {
        $entity = $query->first();

        if ($entity !== null) {
            return $entity;
        }

        $attributes = $useCriteriaAsDefault ? $this->toCriteria($query) : null;

        return $this->repository->entity($attributes ?? []);
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
     * Convert query where clause to key-value criteria
     *
     * @param ReadCommandInterface $query
     *
     * @return array<string, mixed>|null
     */
    public function toCriteria(ReadCommandInterface $query): ?array
    {
        if ($query instanceof KeyValueQueryInterface) {
            return $query->statement('where');
        }

        $criteria = [];

        $statements = [];

        // Flatten query with a single level of nesting
        foreach ($query->statement('where') as $statement) {
            if (CompositeExpression::TYPE_AND !== ($statement['glue'] ?? null)) {
                // Only AND composite expression are supported
                return null;
            }

            if (!isset($statement['nested'])) {
                $statements[] = $statement;
            } else {
                /** @psalm-suppress InvalidOperand */
                $statements = [...$statements, ...$statement['nested']];
            }
        }

        foreach ($statements as $statement) {
            if (
                !isset($statement['column'], $statement['glue'], $statement['operator'])
                || $statement['glue'] !== CompositeExpression::TYPE_AND
                || $statement['operator'] !== '=' // @todo support :eq etc...
            ) {
                return null; // Cannot extract complex criteria
            }

            $value = $statement['value'] ?? null;

            if (is_array($value)) {
                return null;
            }

            $criteria[$statement['column']] = $value;
        }

        return $criteria;
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

    /**
     * Extract the current configuration of the extension
     *
     * Only properties that has been changed (i.e. not set to default value) are returned,
     * so an empty array means that the extension has not been configured.
     *
     * @return array
     * @internal Used by JIT
     */
    public function getMetadata(): array
    {
        $metadata = [];

        if ($this->withRelations) {
            $metadata['withRelations'] = $this->withRelations;
        }

        if ($this->withoutRelations) {
            $metadata['withoutRelations'] = $this->withoutRelations;
        }

        if ($this->byOptions) {
            $metadata['byOptions'] = $this->byOptions;
        }

        return $metadata;
    }

    /**
     * Apply metadata extracted from {@see getMetadata()}
     *
     * @param array $metadata
     * @internal Used by JIT
     */
    public function applyMetadata(array $metadata): void
    {
        $this->withRelations = $metadata['withRelations'] ?? [];
        $this->withoutRelations = $metadata['withoutRelations'] ?? [];
        $this->byOptions = $metadata['byOptions'] ?? null;
    }
}
