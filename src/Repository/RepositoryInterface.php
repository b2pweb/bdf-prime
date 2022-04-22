<?php

namespace Bdf\Prime\Repository;

use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Entity\Criteria;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Relations\RelationInterface;
use Bdf\Prime\Repository\Write\WriterInterface;
use Bdf\Prime\Schema\StructureUpgraderInterface;

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
     * @param class-string<T>|T $entity
     * 
     * @return RepositoryInterface<T>
     * @template T as object
     */
    public function repository($entity): ?RepositoryInterface;

    /**
     * Get mapper
     * 
     * @return Mapper<E>
     */
    public function mapper(): Mapper;
    
    /**
     * Get the metadata
     * 
     * @return Metadata
     */
    public function metadata(): Metadata;

    /**
     * Get DBAL connection
     * 
     * @return ConnectionInterface
     */
    public function connection(): ConnectionInterface;

    /**
     * Check if repository is read only
     *
     * @return boolean
     */
    public function isReadOnly(): bool;

    /**
     * Instanciate entity criteria
     * 
     * @param array $criteria
     * 
     * @return Criteria
     */
    public function criteria(array $criteria = []): Criteria;
    
    /**
     * Instantiate entity
     *
     * @param array<string, mixed> $data
     *
     * @return E
     */
    public function entity(array $data = []);

    /**
     * Get the entity class name expected by mapper
     *
     * @return string
     */
    public function entityName(): string;

    /**
     * Get the entity class name to use
     *
     * @return class-string<E>
     */
    public function entityClass(): string;

    /**
     * Create an EntityCollection
     *
     * @param E[] $entities
     *
     * @return EntityCollection<E>
     */
    public function collection(array $entities = []): CollectionInterface;

    /**
     * Get the CollectionFactory of the repository
     *
     * @return CollectionFactory
     */
    public function collectionFactory(): CollectionFactory;

    /**
     * Get defined relation
     *
     * Build object relation defined by user
     *
     * @param string $relationName
     *
     * @return RelationInterface<E, object>
     */
    public function relation(string $relationName): RelationInterface;

    /**
     * Get the repository constraints
     *
     * @param null|string $context  The context alias to apply to constraints
     *
     * @return array
     */
    public function constraints(?string $context = null): array;

    /**
     * Get schema manager of this repository
     *
     * @param bool $force Allowed user to force schema resolver
     *
     * @return StructureUpgraderInterface
     */
    public function schema(bool $force = false): StructureUpgraderInterface;

    /**
     * Get the repository queries
     *
     * @return RepositoryQueryFactory<E>
     */
    public function queries(): RepositoryQueryFactory;

    /**
     * Get the repository writer
     *
     * @return WriterInterface<E>
     */
    public function writer(): WriterInterface;

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
     * @param E $entity
     *
     * @return boolean
     * @throws PrimeException
     */
    #[ReadOperation]
    public function exists($entity): bool;

    /**
     * Refresh the entity form the repository
     *
     * @param E $entity The entity to refresh
     * @param array $criteria Additional criteria
     *
     * @return E|null The refreshed object or null if not exists
     * @throws PrimeException
     */
    #[ReadOperation]
    public function refresh($entity, array $criteria = []);

    /**
     * Insert or update an entity
     *
     * @param E $entity
     *
     * @return int Number of affected entities
     * @throws PrimeException
     */
    #[WriteOperation]
    public function save($entity): int;

    /**
     * Insert an entity
     *
     * @param E $entity
     * @param bool $ignore
     *
     * @return int Number of affected entities
     * @throws PrimeException
     */
    #[WriteOperation]
    public function insert($entity, bool $ignore = false): int;

    /**
     * Update an entity
     *
     * @param E $entity
     * @param string[]|null $attributes
     *
     * @return int Number of affected entities
     * @throws PrimeException
     */
    #[WriteOperation]
    public function update($entity, ?array $attributes = null): int;

    /**
     * Remove an entity
     *
     * @param E $entity
     *
     * @return int Number of affected entities
     * @throws PrimeException
     */
    #[WriteOperation]
    public function delete($entity): int;

    /**
     * Save entity and its relations
     *
     * @param E $entity
     * @param string|array $relations
     *
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function saveAll($entity, $relations): int;

    /**
     * Delete entity and its relations
     *
     * @param E $entity
     * @param string|array $relations
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
