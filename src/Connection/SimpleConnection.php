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
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Schema\SchemaManager;
use Closure;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection as BaseConnection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Exception as DoctrineDBALException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;

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
    protected $name;
    
    /**
     * The schema manager.
     *
     * @var SchemaManager
     */
    private $schema;

    /**
     * @var SqlPlatform
     */
    private $platform;

    /**
     * @var QueryFactoryInterface
     */
    private $factory;

    /**
     * SimpleConnection constructor.
     *
     * @param array $params
     * @param Driver $driver
     * @param Configuration|null $config
     * @param EventManager|null $eventManager
     * @throws DoctrineDBALException
     */
    public function __construct(array $params, Driver $driver, Configuration $config = null, EventManager $eventManager = null)
    {
        /** @psalm-suppress InternalMethod */
        parent::__construct($params, $driver, $config, $eventManager);

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
    public function isConnected()
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
                $this->platform = new SqlPlatform($this->getDatabasePlatform(), $this->getConfiguration()->getTypes());
            } catch (DoctrineDBALException $e) {
                /** @psalm-suppress InvalidScalarArgument */
                throw new DBALException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $this->platform;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, $type, array $fieldOptions = [])
    {
        return $this->platform()->types()->fromDatabase($value, $type, $fieldOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value, $type = null)
    {
        return $this->platform()->types()->toDatabase($value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function builder(PreprocessorInterface $preprocessor = null): Query
    {
        return $this->factory->make(Query::class, $preprocessor);
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $query, PreprocessorInterface $preprocessor = null): CommandInterface
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
    public function from($table, ?string $alias = null): Query
    {
        return $this->builder()->from($table, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($table, array $criteria, array $types = array())
    {
        return $this->from($table)->where($criteria)->delete();
    }
    
    /**
     * {@inheritdoc}
     */
    public function update($table, array $data, array $criteria, array $types = array())
    {
        return $this->from($table)->where($criteria)->update($data, $types);
    }

    /**
     * {@inheritdoc}
     */
    public function insert($table, array $data, array $types = array())
    {
        return $this->from($table)->insert($data);
    }

    /**
     * {@inheritdoc}
     */
    public function select($query, array $bindings = []): ResultSetInterface
    {
        return (new DoctrineResultSet($this->executeQuery($query, $bindings)))->asObject();
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery(string $sql, array $params = [], $types = [], QueryCacheProfile $qcp = null): Result
    {
        $this->prepareLogger();

        return $this->runOrReconnect(fn() => parent::executeQuery($sql, $params, $types, $qcp));
    }

    /**
     * {@inheritdoc}
     */
    public function executeStatement($sql, array $params = [], array $types = [])
    {
        $this->prepareLogger();

        return $this->runOrReconnect(fn() => parent::executeStatement($sql, $params, $types));
    }

    /**
     * {@inheritdoc}
     *
     * @throws PrimeException
     */
    public function prepare(string $sql): Statement
    {
        return $this->runOrReconnect(fn() => parent::prepare($sql));
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

            return new UpdateResultSet($this->executeStatement($statement, $query->getBindings()));
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
     *
     * @psalm-suppress InternalMethod
     */
    protected function executePrepared(Statement $statement, Compilable $query)
    {
        $bindings = $query->getBindings();
        $isRead = $query->type() === Compilable::TYPE_SELECT;

        $this->prepareLogger();

        try {
            $result = $isRead
                ? new DoctrineResultSet($statement->executeQuery($bindings))
                : new UpdateResultSet($statement->executeStatement($bindings))
            ;
        } catch (DoctrineDBALException $exception) {
            // Prepared query on SQLite for PHP < 7.2 invalidates the query when schema change
            // This process may be removed on PHP 7.2
            if ($this->causedBySchemaChange($exception)) {
                $statement = $query->compile(true);
                $result = $isRead
                    ? new DoctrineResultSet($statement->executeQuery($query->getBindings()))
                    : new UpdateResultSet($statement->executeStatement($query->getBindings()))
                ;
            } elseif ($this->causedByLostConnection($exception->getPrevious())) { // If the connection is lost, the query must be recompiled
                $this->close();
                $this->connect();

                $statement = $query->compile(true);
                $result = $isRead
                    ? new DoctrineResultSet($statement->executeQuery($query->getBindings()))
                    : new UpdateResultSet($statement->executeStatement($query->getBindings()))
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
    public function beginTransaction(): bool
    {
        $this->prepareLogger();

        return parent::beginTransaction() ?? true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        $this->prepareLogger();
        
        return parent::commit() ?? true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function rollBack(): bool
    {
        $this->prepareLogger();
        
        return parent::rollBack() ?? true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        parent::close();

        $this->_eventManager->dispatchEvent(ConnectionClosedListenerInterface::EVENT_NAME);
    }

    /**
     * Setup the logger by setting the connection
     *
     * @return void
     */
    protected function prepareLogger(): void
    {
        /** @psalm-suppress InternalMethod */
        $logger = $this->getConfiguration()->getSQLLogger();

        if ($logger && $logger instanceof ConnectionAwareInterface) {
            $logger->setConnection($this);
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
                    /** @psalm-suppress InternalMethod */
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
