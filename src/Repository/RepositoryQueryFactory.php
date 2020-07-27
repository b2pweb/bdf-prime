<?php

namespace Bdf\Prime\Repository;

use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\QueryException;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\OrmPreprocessor;
use Bdf\Prime\Query\Contract\Cachable;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\QueryRepositoryExtension;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Factory for repository queries
 */
class RepositoryQueryFactory
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var ConnectionInterface
     */
    private $connection;

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
     * @var KeyValueQueryInterface
     */
    private $findByIdQuery;

    /**
     * @var KeyValueQueryInterface
     */
    private $countKeyValueQuery;

    /**
     * Save extension instance for optimisation
     *
     * @var QueryRepositoryExtension
     */
    private $extension;


    /**
     * RepositoryQueryFactory constructor.
     *
     * @param RepositoryInterface $repository
     * @param CacheInterface $resultCache
     */
    public function __construct(RepositoryInterface $repository, CacheInterface $resultCache = null)
    {
        $this->repository = $repository;
        $this->resultCache = $resultCache;

        $this->supportsKeyValue = empty($repository->constraints());

        $this->connection = $repository->connection();
        $this->queries = $repository->mapper()->queries();
        $this->metadata = $repository->metadata();
    }

    /**
     * Get query builder
     *
     * @return QueryInterface
     */
    public function builder()
    {
        return $this->configure($this->connection->builder(new OrmPreprocessor($this->repository)));
    }

    /**
     * Make a query
     *
     * @param string $query The query name or class name to make
     *
     * @return CommandInterface
     */
    public function make($query)
    {
        return $this->configure($this->connection->make($query, new OrmPreprocessor($this->repository)));
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
     * @return mixed The entity or null if not found
     */
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
                throw new QueryException('Only primary keys must be passed to findById()');
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
     * @return KeyValueQueryInterface|null The query, or null if not supported
     */
    public function keyValue($attribute = null, $value = null)
    {
        if (!$this->supportsKeyValue) {
            return null;
        }

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
     */
    public function countKeyValue($attribute = null, $value = null)
    {
        if (!$this->supportsKeyValue) {
            $query = $this->builder();
        } else {
            if (!$this->countKeyValueQuery) {
                $this->countKeyValueQuery = $this->keyValue();
            }

            $query = $this->countKeyValueQuery;
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
     * @param object[] $entities Array of entities to select
     *
     * @return QueryInterface
     */
    public function entities(array $entities)
    {
        $query = $this->repository->queries()->builder();

        if ($this->repository->mapper()->metadata()->isCompositePrimaryKey()) {
            foreach ($entities as $entity) {
                $query->orWhere($this->repository->mapper()->primaryCriteria($entity));
            }
        } else {
            $attribute = $this->repository->mapper()->metadata()->primary['attributes'][0];
            $keys = [];

            foreach ($entities as $entity) {
                $keys[] = $this->repository->extractOne($entity, $attribute);
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
     *
     * @return CommandInterface
     */
    private function configure(CommandInterface $query)
    {
        if ($this->metadata->useQuoteIdentifier) {
            $query->useQuoteIdentifier();
        }

        $query->setCustomFilters($this->repository->mapper()->filters());
        $query->from($this->metadata->table);

        if ($query instanceof ReadCommandInterface) {
            $query->setCollectionFactory($this->repository->collectionFactory());
            $this->extension()->apply($query);
        }

        if ($query instanceof Cachable) {
            $query->setCache($this->resultCache);
        }

        return $query;
    }

    /**
     * Optimise query extension creation
     *
     * @return QueryRepositoryExtension
     */
    private function extension()
    {
        if (!$this->extension) {
            $this->extension = new QueryRepositoryExtension($this->repository);
        }

        return clone $this->extension;
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
