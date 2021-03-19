<?php

namespace Bdf\Prime\Repository;

use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Events;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Repository\Write\WriterInterface;

/**
 * RepositoryInterface
 * 
 * @template E as object
 */
interface RepositoryInterface
{
    /**
     * Get a repository
     * 
     * @param string|object $entity
     * 
     * @return \Bdf\Prime\Repository\RepositoryInterface
     */
    public function repository($entity);
    
    /**
     * Get mapper
     * 
     * @return \Bdf\Prime\Mapper\Mapper
     */
    public function mapper();
    
    /**
     * Get the metadata
     * 
     * @return \Bdf\Prime\Mapper\Metadata
     */
    public function metadata();

    /**
     * Get DBAL connection
     * 
     * @return ConnectionInterface
     */
    public function connection();

    /**
     * Check if repository is read only
     *
     * @return boolean
     */
    public function isReadOnly();

    /**
     * Instanciate entity criteria
     * 
     * @param array $criteria
     * 
     * @return \Bdf\Prime\Entity\Criteria
     */
    public function criteria(array $criteria = []);
    
    /**
     * Instanciate entity
     *
     * @param array $data
     *
     * @return object
     */
    public function entity(array $data = []);

    /**
     * Get the entity class name expected by mapper
     *
     * @return string
     */
    public function entityName();

    /**
     * Get the entity class name to use
     *
     * @return string
     */
    public function entityClass();

    /**
     * Create an EntityCollection
     *
     * @param array $entities
     *
     * @return EntityCollection
     */
    public function collection(array $entities = []);

    /**
     * Get the CollectionFactory of the repository
     *
     * @return CollectionFactory
     */
    public function collectionFactory();

    /**
     * Get defined relation
     *
     * Build object relation defined by user
     *
     * @param string $relationName
     *
     * @return \Bdf\Prime\Relations\RelationInterface
     */
    public function relation($relationName);

    /**
     * Get the repository constraints
     *
     * @param null|string $context  The context alias to apply to constraints
     *
     * @return array
     */
    public function constraints($context = null);

    /**
     * Get schema manager of this repository
     *
     * @param bool $force Allowed user to force schema resolver
     *
     * @return \Bdf\Prime\Schema\ResolverInterface
     */
    public function schema($force = false);

    /**
     * Get the repository queries
     *
     * @return RepositoryQueryFactory
     */
    public function queries();

    /**
     * Get the repository writer
     *
     * @return WriterInterface
     */
    public function writer();

    /**
     * Count entity
     *
     * @param array $criteria
     * @param string|array|null $attributes
     *
     * @return int
     * @throws PrimeException
     */
    #[ReadOperation]
    public function count(array $criteria = [], $attributes = null): int;

    /**
     * Assert that entity exists in repository
     *
     * @param object $entity
     *
     * @return boolean
     * @throws PrimeException
     */
    #[ReadOperation]
    public function exists($entity): bool;

    /**
     * Refresh the entity form the repository
     *
     * @param object $entity The entity to refresh
     * @param array $criteria Additional criteria
     *
     * @return object|null The refreshed object or null if not exists
     * @throws PrimeException
     */
    #[ReadOperation]
    public function refresh($entity, array $criteria = []);

    /**
     * Insert or update an entity
     *
     * @param object $entity
     *
     * @return int Number of affected entities
     * @throws PrimeException
     */
    #[WriteOperation]
    public function save($entity): int;

    /**
     * Insert an entity
     *
     * @param object $entity
     * @param bool   $ignore
     *
     * @return int Number of affected entities
     * @throws PrimeException
     */
    #[WriteOperation]
    public function insert($entity, $ignore = false): int;

    /**
     * Update an entity
     *
     * @param object $entity
     * @param string[]|null $attributes
     *
     * @return int Number of affected entities
     * @throws PrimeException
     */
    #[WriteOperation]
    public function update($entity, ?array $attributes = null): int;

    /**
     * Remove a entity
     *
     * @param object $entity
     *
     * @return int Number of affected entities
     * @throws PrimeException
     */
    #[WriteOperation]
    public function delete($entity): int;

    /**
     * Save entity and its relations
     *
     * @param object         $entity
     * @param string|array   $relations
     *
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function saveAll($entity, $relations): int;

    /**
     * Delete entity and its relations
     *
     * @param object         $entity
     * @param string|array   $relations
     *
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function deleteAll($entity, $relations): int;

    /**
     * Launch transactionnal queries
     *
     * @param callable(EntityRepository):R $work
     * @return R
     *
     * @throws \Exception
     * @throws PrimeException
     *
     * @template R
     */
    public function transaction(callable $work);
}
