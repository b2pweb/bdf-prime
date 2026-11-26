<?php

namespace Bdf\Prime\Relations;

use BadMethodCallException;
use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Collection\Indexer\EntityIndexerInterface;
use Bdf\Prime\Collection\Indexer\EntitySetIndexer;
use Bdf\Prime\Locatorizable;
use Bdf\Prime\Query\Contract\Deletable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Relations\Info\LocalHashTableRelationInfo;
use Bdf\Prime\Relations\Info\NullRelationInfo;
use Bdf\Prime\Relations\Info\RelationInfoInterface;
use Bdf\Prime\Repository\RepositoryInterface;
use Closure;

use function is_array;
use function is_iterable;
use function is_object;

/**
 * Base class for define common methods for relations
 *
 * @template L as object
 * @template R as object
 *
 * @implements RelationInterface<L, R>
 */
abstract class AbstractRelation implements RelationInterface
{
    /**
     * Relation target attribute
     *
     * @var string
     */
    protected string $attributeAim;

    /**
     * The local repository of this relation
     *
     * @var RepositoryInterface<L>
     */
    protected RepositoryInterface $local;

    /**
     * The local alias
     */
    protected ?string $localAlias = null;

    /**
     * The distant repository
     *
     * @var RepositoryInterface<R>|null
     */
    protected ?RepositoryInterface $distant;

    /**
     * Global constraints for this relation
     *
     * @var iterable<string,mixed>|Closure
     */
    protected iterable|Closure $constraints = [];

    /**
     * Is the relation not embedded in entity
     */
    protected bool $isDetached = false;

    /**
     * The query's result wrapper
     *
     * @var null|string|callable
     *
     * @see Query::wrapAs()
     * @see RelationBuilder::wrapAs()
     */
    protected mixed $wrapper = null;
    protected RelationInfoInterface $relationInfo;


    /**
     * Set the relation info
     *
     * @param string $attributeAim  The property name that hold the relation
     * @param RepositoryInterface<L> $local
     * @param RepositoryInterface<R>|null $distant
     */
    public function __construct(string $attributeAim, RepositoryInterface $local, ?RepositoryInterface $distant = null)
    {
        $this->attributeAim = $attributeAim;
        $this->local = $local;
        $this->distant = $distant;

        $this->relationInfo = Locatorizable::isActiveRecordEnabled()
            ? new LocalHashTableRelationInfo()
            : NullRelationInfo::instance()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocalAlias(?string $localAlias)
    {
        $this->localAlias = $localAlias;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function localRepository(): RepositoryInterface
    {
        return $this->local;
    }

    /**
     * {@inheritdoc}
     */
    public function localKeyProperty(): ?string
    {
        return null;
    }

    //
    //----------- options
    //

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        if (isset($options['constraints'])) {
            $this->setConstraints($options['constraints']);
        }

        if (!empty($options['detached'])) {
            $this->setDetached(true);
        }

        if (isset($options['wrapper'])) {
            $this->setWrapper($options['wrapper']);
        }

        return $this;
    }

    /**
     * Get the array of options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return [
            'constraints' => $this->constraints,
            'detached'    => $this->isDetached,
            'wrapper'     => $this->wrapper,
        ];
    }

    /**
     * Set the embedded status
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function setDetached(bool $flag)
    {
        $this->isDetached = $flag;

        return $this;
    }

    /**
     * Is the relation embedded
     *
     * @return bool
     */
    public function isDetached(): bool
    {
        return $this->isDetached;
    }

    /**
     * Get the query's result wrapper
     *
     * @return string|callable|null
     */
    public function getWrapper()
    {
        return $this->wrapper;
    }

    /**
     * @param string|callable $wrapper
     *
     * @return $this
     */
    public function setWrapper(string|callable $wrapper)
    {
        $this->wrapper = $wrapper;

        return $this;
    }

    //
    //--------- constraints and query
    //

    /**
     * Set the global constraints for this relation
     *
     * @param iterable<string,mixed>|Closure $constraints
     *
     * @return $this
     */
    public function setConstraints(iterable|Closure $constraints)
    {
        $this->constraints = $constraints;

        return $this;
    }

    /**
     * Get the global constraints of this relation
     *
     * @return iterable<string,mixed>|Closure
     */
    public function getConstraints(): iterable|Closure
    {
        return $this->constraints;
    }

    /**
     * {@inheritdoc}
     */
    public function isLoaded(object $entity): bool
    {
        return $this->relationInfo->isLoaded($entity);
    }

    /**
     * Apply the constraints on query builder
     * Allows overload of global constraints if both constraints are arrays
     *
     * Use prefix on keys if set
     *
     * @param Q $query
     * @param iterable<string,mixed>|callable $constraints
     * @param string|null $context The context is the prefix used by the query to refer to the related repository
     *
     * @return Q
     *
     * @template Q as \Bdf\Prime\Query\Contract\Whereable&ReadCommandInterface
     */
    protected function applyConstraints(ReadCommandInterface $query, iterable|callable $constraints = [], ?string $context = null): ReadCommandInterface
    {
        if (is_array($constraints) && is_array($this->constraints)) {
            $query->where($this->applyContext($context, $constraints + $this->constraints));
        } else {
            $query->where($this->applyContext($context, $this->constraints));
            $query->where($this->applyContext($context, $constraints));
        }

        return $query;
    }

    /**
     * Apply the context prefix on each keys of the array of constraints
     *
     * @todo algo également présent dans EntityRepository::constraints()
     *
     * @param string|null $context
     * @param iterable<string,mixed>|callable $constraints
     *
     * @return iterable<string,mixed>|callable
     */
    protected function applyContext(?string $context, iterable|callable $constraints)
    {
        if (!$context || !is_iterable($constraints)) {
            return $constraints;
        }

        $withContext = [];
        $context .= '.';

        foreach ($constraints as $key => $value) {
            // Skip commands
            if ($key[0] !== ':') {
                $withContext[$context.$key] = $value;
            }
        }

        return $withContext;
    }

    /**
     * Get a query builder from distant entities
     *
     * @param mixed $value
     * @param iterable<string,mixed>|callable $constraints
     * @param class-string|null $queryClass
     *
     * @return ReadCommandInterface<\Bdf\Prime\Connection\ConnectionInterface, R>&Deletable
     */
    protected function query(mixed $value, iterable|callable $constraints = [], ?string $queryClass = null): ReadCommandInterface
    {
        $query = $this->distant->query($queryClass);

        /** @var ReadCommandInterface<\Bdf\Prime\Connection\ConnectionInterface, R>&Deletable */
        return $this->applyConstraints(
            $this->applyWhereKeys($query, $value),
            $constraints
        );
    }

    /**
     * Apply the where constraint on the query
     *
     * @param Q $query
     * @param mixed $value The keys. Can be an array of keys for perform a "IN" query
     *
     * @return Q
     *
     * @template Q as \Bdf\Prime\Query\Contract\Whereable&ReadCommandInterface<\Bdf\Prime\Connection\ConnectionInterface, R>
     */
    abstract protected function applyWhereKeys(ReadCommandInterface $query, mixed $value): ReadCommandInterface;

    //
    //---------- util methods to set and get/set relation, foreign and primary key
    //

    /**
     * Set the relation value of an entity
     *
     * @param L $entity The relation owner
     * @param R|R[]|null $relation The entity to set to the owner. Can be an array of entities
     */
    protected function setRelation(object $entity, array|object|null $relation): void
    {
        if ($this->isDetached) {
            return;
        }

        if ($this->wrapper !== null) {
            $relation = $this->distant->collectionFactory()->wrap((array) $relation, $this->wrapper);
        }

        $this->local->mapper()->hydrateOne($entity, $this->attributeAim, $relation);

        if ($relation !== null) {
            $this->relationInfo->markAsLoaded($entity);
        } else {
            $this->relationInfo->clear($entity);
        }
    }

    /**
     * Get the relation value of an entity
     *
     * @param L $entity
     *
     * @return R|R[]|null The relation object. Can be an array on many relation
     */
    protected function getRelation(object $entity): object|array|null
    {
        if ($this->isDetached) {
            return null;
        }

        $relation = $this->local->mapper()->extractOne($entity, $this->attributeAim);

        if ($relation === null) {
            return null;
        }

        if ($relation instanceof CollectionInterface) {
            return $relation->all();
        }

        /** @var R */
        return $relation;
    }

    /**
     * Get the referenced alias for this query
     *
     * This method returns the local alias in the context of the query
     *
     * @param ReadCommandInterface $query
     *
     * @return string  The alias of the local table
     */
    protected function getLocalAlias(ReadCommandInterface $query): string
    {
        // @todo works ?
        /** @psalm-suppress UndefinedInterfaceMethod */
        if ($this->local === $query->repository()) {
            return '';
        }

        return '$'.$this->localAlias.'>';
    }

    //
    //---------- Relation operations methods
    //

    /**
     * {@inheritdoc}
     */
    public function load(EntityIndexerInterface $collection, array $with = [], iterable|callable $constraints = [], array $without = []): void
    {
        throw new BadMethodCallException('Unsupported operation '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function loadByForeignKeys(array $keys): array
    {
        throw new BadMethodCallException('Unsupported operation '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function associate(object $owner, object $entity): object
    {
        throw new BadMethodCallException('Unsupported operation '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function dissociate(object $owner): object
    {
        throw new BadMethodCallException('Unsupported operation '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function create(object $owner, array $data = []): object
    {
        throw new BadMethodCallException('Unsupported operation '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function add(object $owner, object $related): int
    {
        throw new BadMethodCallException('Unsupported operation '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function saveAll(object $owner, array $relations = []): int
    {
        throw new BadMethodCallException('Unsupported operation '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll(object $owner, array $relations = []): int
    {
        throw new BadMethodCallException('Unsupported operation '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function loadIfNotLoaded(EntityIndexerInterface $collection, array $with = [], iterable|callable $constraints = [], array $without = []): void
    {
        if ($collection->empty()) {
            return;
        }

        // Constraints are set : force loading
        // At least one entity is not loaded : perform loading from database
        if ($constraints || !$this->isAllLoaded($collection->all())) {
            $this->load($collection, $with, $constraints, $without);
            return;
        }

        // Already loaded and no sub-relation to load
        if (empty($with)) {
            return;
        }

        $with = Relation::sanitizeRelations($with);

        $indexer = new EntitySetIndexer($this->distant->mapper());

        foreach ($collection->all() as $owner) {
            if (!$relationValue = $this->getRelation($owner)) {
                continue; // The owner has no relation : skip
            }

            if (is_object($relationValue)) {
                $indexer->push($relationValue);
                continue;
            }

            foreach ($relationValue as $entity) {
                $indexer->push($entity);
            }
        }

        foreach ($with as $relationName => $options) {
            $this->distant->relation($relationName)->loadIfNotLoaded($indexer, $options['relations'], $options['constraints'], $without[$relationName] ?? []);
        }
    }

    /**
     * Check if all entities has loaded the relation
     *
     * @param L[] $collection
     *
     * @return bool
     */
    private function isAllLoaded(array $collection): bool
    {
        foreach ($collection as $entity) {
            if (!$this->isLoaded($entity)) {
                return false;
            }
        }

        return true;
    }
}
