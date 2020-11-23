<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Schema\Adapter\ConstraintTable;
use Bdf\Prime\Schema\Adapter\MapperInfo\MapperInfoConstraintSet;
use Bdf\Prime\Schema\Adapter\MapperInfo\Resolver\MapperInfoForeignKeyResolver;
use Bdf\Prime\Schema\Adapter\Metadata\MetadataTable;
use Bdf\Prime\Schema\Builder\TableBuilder;
use Bdf\Prime\ServiceLocator;
use Bdf\Prime\Mapper\Metadata;
use Doctrine\DBAL\Exception\TableNotFoundException;

/**
 * Schema resolver
 * 
 * manage update on schema
 * 
 * @todo gestion du renommage de champs dans le cas où d'autres attributs ont été changés
 */
class Resolver implements ResolverInterface
{
    /**
     * @var ServiceLocator 
     */
    protected $service;
    
    /**
     * @var Metadata 
     */
    protected $metadata;

    /**
     * @var SchemaManagerInterface
     */
    protected $schema;


    /**
     * @param ServiceLocator $service Prime service
     * @param Metadata $metadata The entity metadata
     * @param SchemaManagerInterface|null $schema If given, force using this schema manager instead of resolving using the configured connection name
     */
    public function __construct(ServiceLocator $service, Metadata $metadata, SchemaManagerInterface $schema = null)
    {
        $this->service  = $service;
        $this->metadata = $metadata;
        $this->schema   = $schema;
    }
    
    /**
     * {@inheritdoc}
     */
    public function migrate($listDrop = true)
    {
        $schema = $this->schema()->useDrop($listDrop);
        $schema->add($this->table());

        if (($schemaSequence = $this->schemaSequence()) !== null) {
            $schemaSequence->useDrop($listDrop);
            $schemaSequence->add($this->sequence());

            $this->insertSequenceId();
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function diff($listDrop = true)
    {
        $queries = $this->schema()
            ->simulate(function (SchemaManagerInterface $schema) use ($listDrop) {
                $schema->useDrop($listDrop);
                $schema->add($this->table());
            })
            ->pending();

        if (($schemaSequence = $this->schemaSequence()) === null) {
            return $queries;
        }

        $sequenceQueries = $schemaSequence
            ->simulate(function (SchemaManagerInterface $schema) use ($listDrop) {
                $schema->useDrop($listDrop);
                $schema->add($this->sequence());
            })
            ->pending();

        return array_merge($queries, $sequenceQueries);
    }

    /**
     * Create table schema from meta
     *
     * @param bool $foreignKeys Add foreign key constraints to the schema ?
     *
     * @return TableInterface
     * @throws PrimeException
     */
    public function table($foreignKeys = false)
    {
        $table = new MetadataTable(
            $this->metadata,
            $this->connection()->platform()->types()
        );

        if ($foreignKeys) {
            $table = new ConstraintTable(
                $table,
                new MapperInfoConstraintSet(
                    $this->service->repository($this->metadata->entityName)->mapper()->info(),
                    [
                        new MapperInfoForeignKeyResolver($this->service)
                    ]
                )
            );
        }

        return $table;
    }

    /**
     * Create sequence schema from meta
     *
     * @return null|TableInterface
     * @throws PrimeException
     */
    public function sequence()
    {
        if (!$this->metadata->isSequencePrimaryKey()) {
            return null;
        }

        $table = new TableBuilder($this->metadata->sequence['table']);
        $table->options($this->metadata->sequence['options']);
        $table->add(
            $this->metadata->sequence['column'],
            $this->connection($this->metadata->sequence['connection'])->platform()->types()->native('bigint')
        );
        $table->primary($this->metadata->sequence['column']);

        return $table->build();
    }
    
    /**
     * Insert sequence id into sequence table
     *
     * @throws PrimeException
     */
    public function insertSequenceId()
    {
        if (!$this->metadata->isSequencePrimaryKey()) {
            return;
        }
        
        $connection = $this->connection($this->metadata->sequence['connection']);
        $table  = $this->metadata->sequence['table'];
        
        $nb = $connection->from($table)->count();
        
        if ($nb == 0) {
            $connection->insert($table, [$this->metadata->sequence['column'] => 0]);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function truncate($cascade =false)
    {
        return $this->schema()->truncate($this->metadata->table, $cascade);
    }
    
    /**
     * {@inheritdoc}
     */
    public function drop()
    {
        try {
            $this->schema()->drop($this->metadata->table);

            if (($schemaSequence = $this->schemaSequence()) !== null) {
                $schemaSequence->drop($this->metadata->sequence['table']);
            }
            
            return true;
        } catch (DBALException $e) {
            if ($e->getPrevious() instanceof TableNotFoundException) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * Get the schema builder
     * 
     * @return SchemaManagerInterface
     * @throws PrimeException
     */
    protected function schema()
    {
        if ($this->schema !== null) {
            return $this->schema;
        }

        return $this->connection()->schema();
    }

    /**
     * Get the schema builder for sequence
     *
     * @return SchemaManagerInterface
     * @throws PrimeException
     */
    protected function schemaSequence()
    {
        if (!$this->metadata->isSequencePrimaryKey()) {
            return null;
        }

        if ($this->schema !== null) {
            return $this->schema;
        }

        return $this->connection($this->metadata->sequence['connection'])->schema();
    }

    /**
     * Get the connection
     * 
     * @param string $profile
     *
     * @return ConnectionInterface
     */
    protected function connection($profile = null)
    {
        return $this->service->connection($profile ?: $this->metadata->connection);
    }
}
