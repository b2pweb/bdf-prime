<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\ServiceLocator;

/**
 * Sequence table
 *
 * generate sequence from table.
 *
 * @extends AbstractGenerator<\Bdf\Prime\Connection\ConnectionInterface&\Doctrine\DBAL\Connection>
 */
class TableGenerator extends AbstractGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function doGenerate($property, array &$data, ServiceLocator $serviceLocator)
    {
        $metadata = $this->mapper()->metadata();

        $connectionName = $metadata->sequence['connection'];

        if ($connectionName !== $metadata->connection) {
            $connection = $serviceLocator->connection($connectionName);
        } else {
            $connection = $this->connection();
        }

        return $data[$property] = $this->incrementSequence($connection, $metadata);
    }

    /**
     * Increment and return the new sequence id
     *
     * @param \Bdf\Prime\Connection\ConnectionInterface&\Doctrine\DBAL\Connection $connection
     * @param Metadata   $metadata
     *
     * @return string  Return the new sequence id
     * @throws PrimeException
     */
    protected function incrementSequence(ConnectionInterface $connection, Metadata $metadata)
    {
        $platform = $connection->platform();

        switch ($platform->name()) {
            case 'mysql':
                $connection->executeUpdate('UPDATE '.$metadata->sequence['table']
                    .' SET '. $metadata->sequence['column'].' = LAST_INSERT_ID('.$metadata->sequence['column'].'+1)');
                return (string) $connection->lastInsertId();

            case 'sqlite':
                $connection->executeUpdate('UPDATE '.$metadata->sequence['table']
                    .' SET '.$metadata->sequence['column'].' = '.$metadata->sequence['column'].'+1');
                return (string) $connection->executeQuery('SELECT '.$metadata->sequence['column']
                    .' FROM '.$metadata->sequence['table'])->fetchOne();

            default:
                return (string) $connection->executeQuery(
                    $platform->grammar()->getSequenceNextValSQL($metadata->sequence['table'])
                )->fetchOne();
        }
    }
}
