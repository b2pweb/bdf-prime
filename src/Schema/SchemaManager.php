<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\Schema\Adapter\Doctrine\DoctrineTable as PrimeTableAdapter;
use Bdf\Prime\Schema\Transformer\Doctrine\TableTransformer;
use Doctrine\DBAL\Schema\Schema as DoctrineSchema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\SchemaDiff as DoctrineSchemaDiff;
use Doctrine\DBAL\Schema\TableDiff as DoctrineTableDiff;
use Doctrine\DBAL\Schema\Table as DoctrineTable;

/**
 * SchemaManager using doctrine schemas
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
        
        return $lastResult;
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
        $databases = $this->getDoctrineManager()->listDatabases($database);
        
        return in_array(strtolower($database), array_map('strtolower', $databases));
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabases()
    {
        return $this->getDoctrineManager()->listDatabases();
    }

    /**
     * {@inheritdoc}
     */
    public function createDatabase($database)
    {
        return $this->push(
            $this->platform->grammar()->getCreateDatabaseSQL($database)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function dropDatabase($database)
    {
        return $this->push(
            $this->platform->grammar()->getDropDatabaseSQL($database)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable($tableName)
    {
        return $this->getDoctrineManager()->tablesExist($tableName);
    }

    /**
     * {@inheritdoc}
     */
    public function loadTable($tableName)
    {
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
    }

    /**
     * {@inheritdoc}
     */
    public function drop($tableName)
    {
        return $this->push(
            $this->platform->grammar()->getDropTableSQL($tableName)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function truncate($tableName, $cascade = false)
    {
        return $this->push(
            $this->platform->grammar()->getTruncateTableSQL($tableName, $cascade)
        );
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
        
        return $this->push(
            $this->platform->grammar()->getAlterTableSQL($diff)
        );
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
