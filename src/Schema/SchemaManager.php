<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Schema\Adapter\Doctrine\DoctrineTable as PrimeTableAdapter;
use Bdf\Prime\Schema\Transformer\Doctrine\TableTransformer;
use Doctrine\DBAL\DBALException as DoctrineDBALException;
use Doctrine\DBAL\Schema\Schema as DoctrineSchema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\SchemaDiff as DoctrineSchemaDiff;
use Doctrine\DBAL\Schema\TableDiff as DoctrineTableDiff;
use Doctrine\DBAL\Schema\Table as DoctrineTable;

/**
 * SchemaManager using doctrine schemas
 *
 * @extends AbstractSchemaManager<\Bdf\Prime\Connection\ConnectionInterface&\Doctrine\DBAL\Connection>
 * @property \Bdf\Prime\Connection\ConnectionInterface&\Doctrine\DBAL\Connection $connection protected
 */
class SchemaManager extends AbstractSchemaManager
{
    /**
     * Queries to execute
     * 
     * @var array
     */
    private $queries = [];

    
    /**
     * Get the doctrine schema manager
     * 
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public function getDoctrineManager()
    {
        return $this->connection->getSchemaManager();
    }
    
    /**
     * Get the queries to execute
     * 
     * @return array
     */
    public function toSql()
    {
        return $this->queries;
    }

    /**
     * {@inheritdoc}
     */
    public function pending()
    {
        return $this->queries;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->queries = [];
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $lastResult = false;
        $queries = $this->queries;
        
        $this->clear();
        
        foreach ($queries as $query) {
            $lastResult = $this->connection->exec($query);
        }
        
        return (bool) $lastResult;
    }

    /**
     * {@inheritdoc}
     */
    public function schema($tables = [])
    {
        if (!is_array($tables)) {
            $tables = [$tables];
        }

        $tables = array_map(function ($table) {
            if ($table instanceof TableInterface) {
                return (new TableTransformer($table, $this->platform))->toDoctrine();
            }

            return $table;
        }, $tables);

        $config = new SchemaConfig();
        $config->setName($this->connection->getDatabase());

        return new DoctrineSchema($tables, [], $config);
    }

    /**
     * {@inheritdoc}
     */
    public function loadSchema()
    {
        return $this->getDoctrineManager()->createSchema();
    }

    /**
     * {@inheritdoc}
     */
    public function hasDatabase($database)
    {
        try {
            $databases = $this->getDoctrineManager()->listDatabases();
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException($e->getMessage(), $e->getCode(), $e);
        }

        return in_array(strtolower($database), array_map('strtolower', $databases));
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabases()
    {
        try {
            return $this->getDoctrineManager()->listDatabases();
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createDatabase($database)
    {
        try {
            return $this->push(
                $this->platform->grammar()->getCreateDatabaseSQL($database)
            );
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dropDatabase($database)
    {
        try {
            return $this->push(
                $this->platform->grammar()->getDropDatabaseSQL($database)
            );
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable($tableName)
    {
        try {
            return $this->getDoctrineManager()->tablesExist($tableName);
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadTable($tableName)
    {
        try {
            $manager = $this->getDoctrineManager();

            $foreignKeys = [];

            if ($this->platform->grammar()->supportsForeignKeyConstraints()) {
                $foreignKeys = $manager->listTableForeignKeys($tableName);
            }

            return new PrimeTableAdapter(
                new DoctrineTable(
                    $tableName,
                    $manager->listTableColumns($tableName),
                    $manager->listTableIndexes($tableName),
                    $foreignKeys
                ),
                $this->connection->platform()->types()
            );
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function drop($tableName)
    {
        try {
            return $this->push(
                $this->platform->grammar()->getDropTableSQL($tableName)
            );
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function truncate($tableName, $cascade = false)
    {
        try {
            return $this->push(
                $this->platform->grammar()->getTruncateTableSQL($tableName, $cascade)
            );
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function diff(TableInterface $newTable, TableInterface $oldTable)
    {
        $comparator = new Comparator();
        $comparator->setListDropColumn($this->useDrop);

        return $comparator->compare(
            $this->schema($oldTable),
            $this->schema($newTable)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function rename($from, $to)
    {
        $diff = new DoctrineTableDiff($from);
        $diff->newName = $to;

        try {
            return $this->push(
                $this->platform->grammar()->getAlterTableSQL($diff)
            );
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function push($queries)
    {
        if ($queries instanceof DoctrineSchema || $queries instanceof DoctrineSchemaDiff) {
            $queries = $queries->toSql($this->platform->grammar());
        }

        foreach ((array)$queries as $query) {
            $this->queries[] = $query;
        }

        if ($this->autoFlush) {
            $this->flush();
        }

        return $this;
    }
}
