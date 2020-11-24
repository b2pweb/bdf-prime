<?php

namespace Bdf\Prime\Repository\Write;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\WriteOperation;

/**
 * Handle write operations on repository
 */
interface WriterInterface
{
    /**
     * Insert an entity into the repository
     *
     * Available options :
     * - ignore (bool) : Ignore the insert if the entity is already created. By default set to false
     *
     * <code>
     * // Perform a simple insert operation
     * $writer->insert($myEntity);
     *
     * // Insert ignore operation, and check if the entity is successfully inserted
     * $inserted = $writer->insert($otherEntity, ['ignore' => true]) === 1;
     * </code>
     *
     * @param object $entity The entity
     * @param array $options Insert options
     *
     * @return int The number of affected rows
     * @throws PrimeException When insert fail
     */
    #[WriteOperation]
    public function insert($entity, array $options = []);

    /**
     * Update an entity to the repository
     *
     * Available options :
     * - attributes (array) : List of attributes to update. If not provided all attributes will be updated
     *
     * <code>
     * // Update all values of the entity
     * $writer->update($entity);
     *
     * // Update only the "name" attributes
     * $writer->update($entity, ['attributes' => ['name']]);
     * </code>
     *
     * @param object $entity The entity
     * @param array $options Update options
     *
     * @return int The number of affected rows
     * @throws PrimeException When update fail
     */
    #[WriteOperation]
    public function update($entity, array $options = []);

    /**
     * Delete an entity from the repository
     *
     * <code>
     * $writer->delete($entity);
     * </code>
     *
     * @param object $entity The entity
     * @param array $options Delete options
     *
     * @return int The number of affected rows
     * @throws PrimeException When delete fail
     */
    #[WriteOperation]
    public function delete($entity, array $options = []);
}
