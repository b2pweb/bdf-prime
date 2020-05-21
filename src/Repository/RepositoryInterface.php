<?php

namespace Bdf\Prime\Repository;

use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Repository\Write\WriterInterface;

/**
 * RepositoryInterface
 * 
 * @package Bdf\Prime\Repository
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
}
