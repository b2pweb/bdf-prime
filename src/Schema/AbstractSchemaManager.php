<?php

namespace Bdf\Prime\Schema;

use BadMethodCallException;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\TransactionManagerInterface;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\Builder\TableBuilder;
use Bdf\Prime\Schema\Builder\TypesHelperTableBuilder;
use Doctrine\DBAL\Exception as DoctrineDBALException;

/**
 * Class AbstractSchemaManager
 *
 * @template C as ConnectionInterface
 * @implements SchemaManagerInterface<C>
 */
abstract class AbstractSchemaManager implements SchemaManagerInterface
{
    /**
     * The database connection instance.
     *
     * @var C
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
     * @param C $connection
     * @throws PrimeException
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->platform = $connection->platform();
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function useDrop(bool $flag = true)
    {
        $this->useDrop = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUseDrop(): bool
    {
        return $this->useDrop;
    }

    /**
     * {@inheritdoc}
     */
    public function table(string $tableName, callable $callback)
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
    public function add($structure)
    {
        if ($this->has($structure->name())) {
            return $this->push(
                $this->diff($structure, $this->load($structure->name()))
            );
        }

        return $this->push($this->schema($structure));
    }

    /**
     * {@inheritdoc}
     */
    public function change(string $tableName, callable $callback)
    {
        $table = $this->load($tableName);
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
    public function simulate(callable $operations = null)
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
    public function transaction(callable $operations)
    {
        $last = $this->autoFlush;
        $this->autoFlush = false;

        if (!$this->connection instanceof TransactionManagerInterface) {
            throw new BadMethodCallException('The connection '.$this->connection->getName().' do not handle transactions');
        }

        try {
            $operations($this);

            $this->connection->beginTransaction();
            $this->flush();
            $this->connection->commit();
        } catch (DoctrineDBALException $e) {
            $this->connection->rollBack();

            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException($e->getMessage(), $e->getCode(), $e);
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
    public function isBuffered(): bool
    {
        return !$this->autoFlush;
    }
}
