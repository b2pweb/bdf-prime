<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Platform\Sql\SqlPlatform;
use Bdf\Prime\Platform\Sql\SqlPlatformOperationInterface;
use Bdf\Prime\Platform\Sql\SqlPlatformOperationTrait;
use Bdf\Prime\ServiceLocator;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;

use LogicException;

use function get_class;
use function method_exists;

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
        $table = $metadata->sequence['table'];
        $column = $metadata->sequence['column'];

        $platform = $connection->platform();

        if (!method_exists($platform, 'apply')) {
            throw new LogicException('The platform ' . get_class($platform) . ' does not support the method apply().');
        }

        return $platform->apply(new class ($connection, $table, $column) implements SqlPlatformOperationInterface {
            use SqlPlatformOperationTrait;

            /** @var \Bdf\Prime\Connection\ConnectionInterface&\Doctrine\DBAL\Connection */
            private ConnectionInterface $connection;
            private string $table;
            private string $column;

            /**
             * @param \Bdf\Prime\Connection\ConnectionInterface&\Doctrine\DBAL\Connection $connection
             */
            public function __construct(ConnectionInterface $connection, string $table, string $column)
            {
                $this->connection = $connection;
                $this->table = $table;
                $this->column = $column;
            }

            public function onMysqlPlatform(SqlPlatform $platform, AbstractMySQLPlatform $grammar): string
            {
                $this->connection->executeStatement('UPDATE '.$this->table.' SET '. $this->column.' = LAST_INSERT_ID('.$this->column.'+1)');
                return (string) $this->connection->lastInsertId();
            }

            public function onSqlitePlatform(SqlPlatform $platform, SqlitePlatform $grammar): string
            {
                $this->connection->executeStatement('UPDATE '.$this->table.' SET '.$this->column.' = '.$this->column.'+1');
                return (string) $this->connection->executeQuery('SELECT '.$this->column.' FROM '.$this->table)->fetchOne();
            }

            public function onGenericSqlPlatform(SqlPlatform $platform, AbstractPlatform $grammar): string
            {
                return (string) $this->connection->executeQuery($grammar->getSequenceNextValSQL($this->table))->fetchOne();
            }
        });
    }
}
