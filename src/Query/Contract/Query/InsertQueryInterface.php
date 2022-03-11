<?php

namespace Bdf\Prime\Query\Contract\Query;

use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\Contract\BulkWriteBuilderInterface;

/**
 * Base type for insert queries
 *
 * <code>
 * // Simple insert
 * $insert
 *     ->into('person')
 *     ->values([
 *         'first_name' => 'John',
 *         'last_name'  => 'Doe'
 *     ])
 *     ->execute()
 * ;
 *
 * // Bulk insert
 * $insert
 *     ->bulk()
 *     ->values([
 *         'first_name' => 'Alan',
 *         'last_name'  => 'Smith'
 *     ])
 *     ->values([
 *         'first_name' => 'Mickey',
 *         'last_name'  => 'Mouse'
 *     ])
 *     ->execute()
 * ;
 * </code>
 *
 * @template C as \Bdf\Prime\Connection\ConnectionInterface
 * @extends CommandInterface<C>
 */
interface InsertQueryInterface extends BulkWriteBuilderInterface, CommandInterface
{
    /**
     * {@inheritdoc}
     *
     * Execute the insert operation
     *
     * @param mixed $columns Not used : only for compatibility with CommandInterface
     *
     * @return ResultSetInterface<array<string, mixed>>
     * @throws PrimeException When execute fail
     */
    public function execute($columns = null): ResultSetInterface;
}
