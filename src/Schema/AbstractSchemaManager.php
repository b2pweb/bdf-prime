<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\Builder\TableBuilder;
use Bdf\Prime\Schema\Builder\TypesHelperTableBuilder;

/**
 * Class AbstractSchemaManager
 */
abstract class AbstractSchemaManager implements SchemaManagerInterface
{
    /**
     * The database connection instance.
     *
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * The connection platform.
     *
     * @var PlatformInterface
     */
    protected $platform;

    /**
     * The use drop flag. Allows builder to use drop command on diff
     *
     * @var bool
     */
    protected $useDrop = true;

    /**
     * The auto flush flag. Allows builder execute query
     *
     * @var bool
     * @internal
     */
    protected $autoFlush = true;



    /**
     * Create a new schema builder.
     *
     * @param  ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->setConnection($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->platform   = $connection->platform();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function useDrop($flag = true)
    {
        $this->useDrop = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUseDrop()
    {
        return $this->useDrop;
    }

    /**
     * {@inheritdoc}
     */
    public function table($tableName, callable $callback)
    {
        $table = new TypesHelperTableBuilder(
            new TableBuilder($tableName),
            $this->platform->types()
        );
        
        $callback($table);

        return $this->add($table->build());
    }

    /**
     * {@inheritdoc}
     */
    public function add(TableInterface $table)
    {
        if ($this->hasTable($table->name())) {
            return $this->push(
                $this->diff($table, $this->loadTable($table->name()))
            );
        }

        return $this->push($this->schema($table));
    }

    /**
     * {@inheritdoc}
     */
    public function change($tableName, callable $callback)
    {
        $table = $this->loadTable($tableName);
        $builder = TableBuilder::fromTable($table);

        $callback(
            new TypesHelperTableBuilder(
                $builder,
                $this->platform->types()
            )
        );

        return $this->push(
            $this->diff($builder->build(), $table)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function simulate(\Closure $operations = null)
    {
        $newSchema = clone $this;
        $newSchema->autoFlush = false;

        if ($operations !== null) {
            $operations($newSchema);
        }

        return $newSchema;
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(\Closure $operations)
    {
        $last = $this->autoFlush;
        $this->autoFlush = false;

        try {
            $operations($this);

            $this->connection->beginTransaction();
            $this->flush();
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();

            throw $e;
        } finally {
            $this->autoFlush = $last;
            $this->clear();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isBuffered()
    {
        return !$this->autoFlush;
    }
}
