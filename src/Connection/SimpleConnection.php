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
use Bdf\Prime\Platform\Sql\SqlPlatform;
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
use Bdf\Prime\Schema\SchemaManager;
use Closure;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection as BaseConnection;
use Doctrine\DBAL\DBALException as DoctrineDBALException;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Statement;
use PDO;

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
    public function setName($name)
    {
        $this->name = $name;
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
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
    public function schema()
    {
        if ($this->schema === null) {
            $this->schema = new SchemaManager($this);
        }

        return $this->schema;
    }

    /**
     * {@inheritdoc}
     */
    public function platform()
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
    public function builder(PreprocessorInterface $preprocessor = null)
    {
        return $this->factory->make(Query::class, $preprocessor);
    }

    /**
     * {@inheritdoc}
     */
    public function make($query, PreprocessorInterface $preprocessor = null)
    {
        return $this->factory->make($query, $preprocessor);
    }

    /**
     * {@inheritdoc}
     */
    public function factory()
    {
        return $this->factory;
    }

    /**
     * {@inheritdoc}
     */
    public function from($table, string $alias = null)
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
     * Executes select query and returns array of object
     * 
     * @param string $query
     * @param array  $bindings
     * @param array  $types
     * 
     * @return array
     */
    public function select($query, array $bindings = [], array $types = [])
    {
        $stmt = $this->executeQuery($query, $bindings, $types);
        $stmt->setFetchMode(PDO::FETCH_OBJ);

        $result = $stmt->fetchAll();

        $stmt->closeCursor();
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery($sql, array $params = [], $types = [], QueryCacheProfile $qcp = null)
    {
        $this->prepareLogger();

        return $this->runOrReconnect(function() use ($sql, $params, $types, $qcp) {
            return parent::executeQuery($sql, $params, $types, $qcp);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function executeUpdate($sql, array $params = [], array $types = [])
    {
        $this->prepareLogger();

        return $this->runOrReconnect(function() use ($sql, $params, $types) {
            return parent::executeUpdate($sql, $params, $types);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        $this->prepareLogger();
        
        $args = func_get_args();

        return $this->runOrReconnect(function() use ($args) {
            return parent::query(...$args);
        });
    }
    
    /**
     * {@inheritdoc}
     *
     * @throws PrimeException
     */
    public function exec($statement)
    {
        $this->prepareLogger();
        
        return $this->runOrReconnect(function() use ($statement) {
            return parent::exec($statement);
        });
    }

    /**
     * {@inheritdoc}
     *
     * @throws PrimeException
     */
    public function prepare($statement)
    {
        return $this->runOrReconnect(function() use ($statement) {
            return parent::prepare($statement);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Compilable $query)
    {
        try {
            $statement = $query->compile();

            if ($statement instanceof Statement) {
                return $this->executePrepared($statement, $query);
            }

            // $statement is a SQL query
            if ($query->type() === Compilable::TYPE_SELECT) {
                $stmt = $this->executeQuery($statement, $query->getBindings());

                return new DoctrineResultSet($stmt);
            }

            return new UpdateResultSet($this->executeUpdate($statement, $query->getBindings()));
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException('Error on execute : '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Execute a prepared statement
     *
     * @param Statement $statement
     * @param Compilable $query
     *
     * @return ResultSetInterface The query result
     * @throws DoctrineDBALException
     * @throws PrimeException
     */
    protected function executePrepared(Statement $statement, Compilable $query)
    {
        $bindings = $query->getBindings();

        $this->prepareLogger();

        try {
            $statement->execute($bindings);
        } catch (DoctrineDBALException $exception) {
            // Prepared query on SQLite for PHP < 7.2 invalidates the query when schema change
            // This process may be removed on PHP 7.2
            if ($this->causedBySchemaChange($exception)) {
                $statement = $query->compile(true);
                $statement->execute($query->getBindings());
            } elseif ($this->causedByLostConnection($exception->getPrevious())) { // If the connection is lost, the query must be recompiled
                $this->close();
                $this->connect();

                $statement = $query->compile(true);
                $statement->execute($query->getBindings());
            } else {
                throw $exception;
            }
        }

        return new DoctrineResultSet($statement);
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
    public function close()
    {
        parent::close();

        $this->_eventManager->dispatchEvent(ConnectionClosedListenerInterface::EVENT_NAME);
    }

    /**
     * Setup the logger by setting the connection
     */
    protected function prepareLogger()
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
     * @param Closure $callback
     *
     * @return mixed  The query result
     *
     * @throws DBALException
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
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException('Error on execute : '.$e->getMessage(), $e->getCode(), $e);
        }
    }
}
