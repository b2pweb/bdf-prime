<?php

namespace Bdf\Prime\Repository;

use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Exception\QueryBuildingException;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\Closure\ClosureCompiler;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\OrmPreprocessor;
use Bdf\Prime\Query\Contract\Cachable;
use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Pagination\PaginatorFactory;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\QueryRepositoryExtension;
use Bdf\Prime\Query\ReadCommandInterface;
use Psr\SimpleCache\CacheInterface as Psr16Cache;

/**
 * Factory for repository queries
 *
 * @template E as object
 * @mixin QueryInterface<ConnectionInterface, E>
 */
class RepositoryQueryFactory
{
    /**
     * @var RepositoryInterface<E>
     */
    private $repository;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var callable[]
     */
    private $queries;

    /**
     * Query result cache
     *
     * @var CacheInterface
     */
    private $resultCache;

    /**
     * @var Psr16Cache|null
     */
    private $metadataCache;

    /**
     * Check if the repository can support optimised KeyValue query
     * If this value is false, keyValue() must returns null
     *
     * @var bool
     */
    private $supportsKeyValue;

    //===============//
    // Optimisations //
    //===============//

    /**
     * @var KeyValueQueryInterface<ConnectionInterface, E>
     */
    private $findByIdQuery;

    /**
     * @var array<array-key, KeyValueQueryInterface<ConnectionInterface, E>|null>
     */
    private $countKeyValueQueries;

    /**
     * Save extension instance for optimisation
     *
     * @var QueryRepositoryExtension<E>
     */
    private $extension;

    /**
     * Save paginator factory instance for optimisation
     *
     * @var PaginatorFactory
     */
    private $paginatorFactory;


    /**
     * RepositoryQueryFactory constructor.
     *
     * @param RepositoryInterface<E> $repository
     * @param CacheInterface|null $resultCache
     */
    public function __construct(RepositoryInterface $repository, ?CacheInterface $resultCache = null, ?Psr16Cache $metadataCache = null)
    {
        $this->repository = $repository;
        $this->resultCache = $resultCache;
        $this->metadataCache = $metadataCache;

        $this->supportsKeyValue = empty($repository->constraints());

        $this->queries = $repository->mapper()->queries();
        $this->metadata = $repository->metadata();
    }

    /**
     * Get query builder
     *
     * @return QueryInterface<ConnectionInterface, E>
     */
    public function builder()
    {
        return $this->fromAlias();
    }

    /**
     * Get query builder with a defined table alias on FROM clause
     *
     * @param string|null $alias The FROM table alias
     *
     * @return QueryInterface<ConnectionInterface, E>
     *
     * @throws PrimeException
     */
    public function fromAlias(?string $alias = null)
    {
        return $this->configure($this->repository->connection()->builder(new OrmPreprocessor($this->repository)), $alias);
    }

    /**
     * Make a query
     *
     * @param class-string<Q> $query The query name or class name to make
     *
     * @return Q
     * @throws PrimeException When cannot create the query
     *
     * @template Q as object
     *
     * @todo typehint with CommandInterface
     * @psalm-suppress InvalidReturnType
     */
    public function make(string $query)
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->configure($this->repository->connection()->make($query, new OrmPreprocessor($this->repository)));
    }

    /**
     * Find entity by its primary key
     *
     * <code>
     * $queries->findById(2);
     * $queries->findById(['key1' => 1, 'key2' => 5]);
     * </code>
     *
     * @param array|string $id The entity PK. Use an array for composite PK
     *
     * @return E|null The entity or null if not found
     * @throws PrimeException When query fail
     */
    #[ReadOperation]
    public function findById($id)
    {
        // Create a new query if cache is disabled
        if (!$this->supportsKeyValue) {
            $query = $this->builder();
        } else {
            if (!$this->findByIdQuery) {
                $this->findByIdQuery = $this->keyValue();
            }

            $query = $this->findByIdQuery;
        }

        if (is_array($id)) {
            if (!$this->isPrimaryKeyFilter($id)) {
                throw new QueryBuildingException('Only primary keys must be passed to findById()');
            }

            $query->where($id);
        } else {
            list($identifierName) = $this->metadata->primary['attributes'];
            $query->where($identifierName, $id);
        }

        return $query->first();
    }

    /**
     * Create a query for perform simple key / value search on the current repository
     *
     * /!\ Key value query cannot perform join queries (condition on relation is not allowed)
     *     And can perform only equality comparison
     *
     * <code>
     * // Search by name
     * $queries->keyValue('name', 'myName')->first();
     *
     * // Get an empty key value query
     * $queries->keyValue()->where(...);
     *
     * // With criteria
     * $queries->keyValue(['name' => 'John', 'customer.id' => 5])->all();
     * </code>
     *
     * @param string|array|null $attribute The search attribute, or criteria
     * @param mixed $value The search value
     *
     * @return KeyValueQueryInterface<ConnectionInterface, E>|null The query, or null if not supported
     */
    public function keyValue($attribute = null, $value = null)
    {
        if (!$this->supportsKeyValue) {
            return null;
        }

        /** @var KeyValueQueryInterface<ConnectionInterface, E> $query */
        $query = $this->make(KeyValueQueryInterface::class);

        if ($attribute) {
            $query->where($attribute, $value);
        }

        return $query;
    }

    /**
     * Count rows on the current table with simple key / value search
     *
     * /!\ Key value query cannot perform join queries (condition on relation is not allowed)
     *     And can perform only equality comparison
     *
     * <code>
     * // Count entities with myName as name value
     * $queries->countKeyValue('name', 'myName');
     *
     * // With criteria
     * $queries->countKeyValue(['name' => 'John', 'customer.id' => 5]);
     * </code>
     *
     * @param string|array|null $attribute The search attribute, or criteria
     * @param mixed $value The search value
     *
     * @return int
     * @throws PrimeException When query fail
     */
    #[ReadOperation]
    public function countKeyValue($attribute = null, $value = null)
    {
        if (!$this->supportsKeyValue) {
            $query = $this->builder();
        } elseif (is_array($attribute)) {
            $query = $this->keyValue();
        } else {
            $query = $this->countKeyValueQueries[$attribute ?? 0] ?? null;

            if (!$query) {
                $this->countKeyValueQueries[$attribute ?? 0] = $query = $this->keyValue();
            }
        }

        if ($attribute) {
            $query->where($attribute, $value);
        }

        return $query->count();
    }

    /**
     * Create a query selecting all entities
     *
     * This method will configure query like :
     * SELECT * FROM entity WHERE pk IN (entity1.pk, entity2.pk, ...)
     *
     * /!\ All entities MUST have a valid primary key !
     *
     * <code>
     * // True if modifications occurs on database
     * $hasChanges = ($entities != $queries->entities($entities)->all());
     *
     * // Delete all entities
     * $queries->entities($entities)->delete();
     * </code>
     *
     * @param E[] $entities Array of entities to select
     *
     * @return QueryInterface<ConnectionInterface, E>
     */
    public function entities(array $entities)
    {
        $query = $this->builder();
        $mapper = $this->repository->mapper();

        if ($this->metadata->isCompositePrimaryKey()) {
            foreach ($entities as $entity) {
                $query->orWhere($mapper->primaryCriteria($entity));
            }
        } else {
            $attribute = $this->metadata->primary['attributes'][0];
            $keys = [];

            foreach ($entities as $entity) {
                $keys[] = $mapper->extractOne($entity, $attribute);
            }

            $query->where($attribute, 'in', $keys);
        }

        return $query;
    }

    /**
     * Delegates call to corresponding query
     *
     * @param string $name
     * @param string $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (isset($this->queries[$name])) {
            return $this->queries[$name]($this->repository, ...$arguments);
        }

        return $this->builder()->$name(...$arguments);
    }

    /**
     * Configure the query for the current repository
     *
     * @param CommandInterface $query
     * @param string|null $alias The FROM table alias
     *
     * @return CommandInterface
     *
     * @template Q
     * @psalm-param Q $query
     * @psalm-return Q
     */
    private function configure(CommandInterface $query, ?string $alias = null)
    {
        if ($this->metadata->useQuoteIdentifier) {
            $query->useQuoteIdentifier();
        }

        if (method_exists($query, 'allowUnknownAttribute')) {
            $query->allowUnknownAttribute($this->repository->mapper()->allowUnknownAttribute());
        }

        $query->setCustomFilters($this->repository->mapper()->filters());
        $query->from($this->metadata->table, $alias);

        if ($query instanceof ReadCommandInterface) {
            $query->setCollectionFactory($this->repository->collectionFactory());
            $this->extension()->apply($query);
        }

        if ($query instanceof Cachable) {
            $query->setCache($this->resultCache);
        }

        if ($query instanceof Paginable) {
            $query->setPaginatorFactory($this->paginatorFactory());
        }

        return $query;
    }

    /**
     * Optimise query extension creation
     *
     * @return QueryRepositoryExtension<E>
     */
    private function extension()
    {
        if (!$this->extension) {
            $this->extension = new QueryRepositoryExtension(
                $this->repository,
                new ClosureCompiler($this->repository, $this->metadataCache)
            );
        }

        return clone $this->extension;
    }

    /**
     * Get the paginator factory instance
     *
     * @return PaginatorFactory
     */
    private function paginatorFactory(): PaginatorFactory
    {
        if ($this->paginatorFactory) {
            return $this->paginatorFactory;
        }

        return $this->paginatorFactory = new RepositoryPaginatorFactory($this->repository);
    }

    /**
     * Check if the given filter exactly match with primary key attributes
     *
     * @param array $filter
     *
     * @return bool
     */
    private function isPrimaryKeyFilter(array $filter): bool
    {
        $pk = $this->metadata->primary['attributes'];

        if (count($filter) !== count($pk)) {
            return false;
        }

        foreach ($pk as $key) {
            if (!isset($filter[$key])) {
                return false;
            }
        }

        return true;
    }
}
