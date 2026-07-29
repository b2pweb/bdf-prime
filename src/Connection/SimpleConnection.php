<?php

namespace Bdf\Prime\Connection;

use Bdf\Prime\Connection\Event\ConnectionClosedListenerInterface;
use Bdf\Prime\Connection\Extensions\LostConnection;
use Bdf\Prime\Connection\Extensions\SchemaChanged;
use Bdf\Prime\Connection\Result\DoctrineResultSet;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Connection\Result\UpdateResultSet;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Exception\QueryExecutionException;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\SqlPlatform;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Compiler\SqlCompiler;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use Bdf\Prime\Query\Custom\BulkInsert\BulkInsertQuery;
use Bdf\Prime\Query\Custom\BulkInsert\BulkInsertSqlCompiler;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueSqlCompiler;
use Bdf\Prime\Query\Factory\DefaultQueryFactory;
use Bdf\Prime\Query\Factory\QueryFactoryInterface;
use Bdf\Prime\Query\Query;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Schema\SchemaManager;
use Bdf\Prime\Types\TypeInterface;
use Closure;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection as BaseConnection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Exception as DoctrineDBALException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Throwable;

use function spl_object_id;
use function trigger_error;

/**
 * Connection
 *
 * @method \Bdf\Prime\Configuration getConfiguration()
 */
class SimpleConnection extends BaseConnection implements ConnectionInterface, TransactionManagerInterface
{
    use LostConnection;
    use SchemaChanged;

    /**
     * The connection name.
     *
     * @var string
     */
    protected string $name;

    /**
     * The schema manager.
     */
    private ?SchemaManager $schema = null;

    private ?SqlPlatform $platform = null;

    private DefaultQueryFactory $factory;

    /**
     * List of listeners to call when the connection is closed,
     * indexed by the listener object id
     *
     * @var array<int, Closure(ConnectionInterface):void>
     */
    private array $onConnectionClosedListeners = [];

    /**
     * SimpleConnection constructor.
     *
     * @param array $params
     * @param Driver $driver
     * @param Configuration|null $config
     * @throws DoctrineDBALException
     */
    public function __construct(array $params, Driver $driver, ?Configuration $config = null)
    {
        /** @psalm-suppress InternalMethod */
        parent::__construct($params, $driver, $config);

        /** @psalm-suppress InvalidArgument */
        $this->factory = new DefaultQueryFactory(
            $this,
            new SqlCompiler($this),
            [
                KeyValueQuery::class   => KeyValueSqlCompiler::class,
                BulkInsertQuery::class => BulkInsertSqlCompiler::class,
            ],
            [
                KeyValueQueryInterface::class => KeyValueQuery::class,
                InsertQueryInterface::class   => BulkInsertQuery::class,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(): ?string
    {
        return parent::getDatabase();
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        /** @psalm-suppress InternalMethod */
        return $this->getParams();
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool
    {
        return $this->_conn !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function schema(): SchemaManager
    {
        if ($this->schema === null) {
            $this->schema = new SchemaManager($this);
        }

        return $this->schema;
    }

    /**
     * {@inheritdoc}
     */
    public function platform(): PlatformInterface
    {
        if ($this->platform === null) {
            try {
                $config = $this->getConfiguration();
                $this->platform = new SqlPlatform($this->getDatabasePlatform(), $config->getTypes());
                $types = $this->platform->types();

                foreach ($config->getPlatformTypes() as $alias => $type) {
                    $types->register($type, is_string($alias) ? $alias : null);
                }
            } catch (DoctrineDBALException $e) {
                /** @psalm-suppress InvalidScalarArgument */
                throw new DBALException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $this->platform;
    }

    /**
     * {@inheritdoc}
     *
     * @param Closure(ConnectionInterface):void $listener
     */
    public function addConnectionClosedListener(Closure $listener): void
    {
        $id = spl_object_id($listener);

        $this->onConnectionClosedListeners[$id] = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function removeConnectionClosedListener(Closure $listener): void
    {
        $id = spl_object_id($listener);

        unset($this->onConnectionClosedListeners[$id]);
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase(mixed $value, string|TypeInterface $type, array $fieldOptions = []): mixed
    {
        return $this->platform()->types()->fromDatabase($value, $type, $fieldOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase(mixed $value, string|TypeInterface|null $type = null): mixed
    {
        return $this->platform()->types()->toDatabase($value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function builder(?PreprocessorInterface $preprocessor = null): Query
    {
        return $this->factory->make(Query::class, $preprocessor);
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $query, ?PreprocessorInterface $preprocessor = null): CommandInterface
    {
        return $this->factory->make($query, $preprocessor);
    }

    /**
     * {@inheritdoc}
     */
    public function factory(): QueryFactoryInterface
    {
        return $this->factory;
    }

    /**
     * {@inheritdoc}
     */
    public function from(string|QueryInterface $table, ?string $alias = null): Query
    {
        return $this->builder()->from($table, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $table, array $criteria = [], array $types = []): int|string
    {
        return $this->from($table)->where($criteria)->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $table, array $data, array $criteria = [], array $types = []): int|string
    {
        return $this->from($table)->where($criteria)->update($data, $types);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(string $table, array $data, array $types = []): int|string
    {
        return $this->from($table)->insert($data);
    }

    /**
     * {@inheritdoc}
     */
    public function select(mixed $query, array $bindings = []): ResultSetInterface
    {
        return (new DoctrineResultSet($this->executeQuery($query, $bindings)))->asObject();
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery(string $sql, array $params = [], array $types = [], ?QueryCacheProfile $qcp = null): Result
    {
        $types = $types ?: Binder::types($params);

        return $this->runOrReconnect(fn () => parent::executeQuery($sql, $params, $types, $qcp));
    }

    /**
     * {@inheritdoc}
     */
    public function executeStatement(string $sql, array $params = [], array $types = []): int|string
    {
        $types = $types ?: Binder::types($params);

        return $this->runOrReconnect(fn () => parent::executeStatement($sql, $params, $types));
    }

    /**
     * {@inheritdoc}
     *
     * @throws PrimeException
     */
    public function prepare(string $sql): Statement
    {
        return $this->runOrReconnect(fn () => parent::prepare($sql));
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Compilable $query): ResultSetInterface
    {
        try {
            $statement = $query->compile();

            if ($statement instanceof Statement) {
                return $this->executePrepared($statement, $query);
            }

            // $statement is a SQL query
            if ($query->type() === Compilable::TYPE_SELECT) {
                return new DoctrineResultSet($this->executeQuery($statement, $query->getBindings()));
            }

            return new UpdateResultSet((int) $this->executeStatement($statement, $query->getBindings()));
        } catch (DriverException $e) {
            throw new QueryExecutionException(
                'Error on execute : ' . $e->getMessage(),
                $e->getCode(),
                $e,
                $e->getQuery() ? $e->getQuery()->getSQL() : null,
                $e->getQuery() ? $e->getQuery()->getParams() : null
            );
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new QueryExecutionException('Error on execute : '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Execute a prepared statement
     *
     * @param Statement $statement
     * @param Compilable $query
     *
     * @return ResultSetInterface The query result
     *
     * @throws DoctrineDBALException
     * @throws PrimeException
     */
    protected function executePrepared(Statement $statement, Compilable $query)
    {
        $statement = Binder::bindValues($statement, $query);
        $isRead = $query->type() === Compilable::TYPE_SELECT;

        try {
            $result = $isRead
                ? new DoctrineResultSet($statement->executeQuery())
                : new UpdateResultSet((int) $statement->executeStatement())
            ;
        } catch (DoctrineDBALException $exception) {
            // Prepared query on SQLite for PHP < 7.2 invalidates the query when schema change
            // This process may be removed on PHP 7.2
            if ($this->causedBySchemaChange($exception)) {
                $statement = Binder::bindValues($query->compile(true), $query);
                $result = $isRead
                    ? new DoctrineResultSet($statement->executeQuery())
                    : new UpdateResultSet((int) $statement->executeStatement())
                ;
            } elseif ($this->causedByLostConnection($exception->getPrevious())) { // If the connection is lost, the query must be recompiled
                $this->close();
                $this->connect();

                $statement = Binder::bindValues($query->compile(true), $query);
                $result = $isRead
                    ? new DoctrineResultSet($statement->executeQuery())
                    : new UpdateResultSet((int) $statement->executeStatement())
                ;
            } else {
                throw $exception;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function inTransaction(callable $task): mixed
    {
        $this->beginTransaction();

        try {
            $result = $task();
            $this->commit();

            return $result;
        } catch (Throwable $e) {
            $this->rollBack();

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function inNestedTransaction(callable $task)
    {
        @trigger_error('The method ' . __METHOD__ . ' is deprecated since Prime 3.0, use inTransaction() instead', E_USER_DEPRECATED);

        return $this->inTransaction($task);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        parent::close();

        foreach ($this->onConnectionClosedListeners as $listener) {
            $listener($this);
        }
    }

    /**
     * Execute a query. Try to reconnect if needed
     *
     * @param Closure():T $callback
     *
     * @return T The query result
     *
     * @throws QueryExecutionException When an error occurs during query execution
     * @throws DBALException When any other error occurs
     *
     * @template T
     */
    protected function runOrReconnect(Closure $callback)
    {
        try {
            try {
                return $callback();
            } catch (DoctrineDBALException $exception) {
                if ($this->causedByLostConnection($exception->getPrevious())) {
                    // Should check for active transaction.
                    // Only reconnect the start transaction.
                    // Should raise exception during transaction.
                    $this->close();
                    $this->connect();

                    return $callback();
                }

                throw $exception;
            }
        } catch (DriverException $e) {
            throw new QueryExecutionException(
                'Error on execute : ' . $e->getMessage(),
                $e->getCode(),
                $e,
                $e->getQuery() ? $e->getQuery()->getSQL() : null,
                $e->getQuery() ? $e->getQuery()->getParams() : null
            );
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException('Error on execute : '.$e->getMessage(), $e->getCode(), $e);
        }
    }
}
