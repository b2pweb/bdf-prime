<?php

namespace Bdf\Prime\Sharding;

use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Connection\SubConnectionManagerInterface;
use Bdf\Prime\Exception\ShardingException;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use Bdf\Prime\Query\Factory\DefaultQueryFactory;
use Bdf\Prime\Sharding\Query\ShardingInsertQuery;
use Bdf\Prime\Sharding\Query\ShardingKeyValueQuery;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Result;
use LogicException;

/**
 * ShardingConnection
 *
 * The sharding connection is a global connection (that can be a shard server) and a collection of connection wrappers for shards.
 *
 * Those methods will be used by the global connection:
 *    ShardingConnection#prepare    Will connect on global but should execute on shard if one is selected
 *    ShardingConnection#quote
 *    ShardingConnection#errorCode
 *    ShardingConnection#errorInfo
 *    ShardingConnection#ping
 *
 * Can connect the global connection if no shard has been selected:
 *    ShardingConnection#lastInsertId
 *    ShardingConnection#lastInsertId
 *
 * Be aware!!
 * This connection does not managed the distribution key. If a SQL INSERT is executed without shard selection
 * the SQL will be executed on each shards.
 *
 * The shard to use can be auto guess if query builder is used.
 *
 * The method MultiStatement#fetchColumn change the interface (it will return a array of result)
 * All aggregate function executed on each shard will return a collection of result.
 * The merge should be done outside this class
 *
 * @example:
 * // returns the count result of the first shard
 * $connection->from('test')->count());
 *
 * // returns an array containing all count result of each shards
 * $connection->query('select count(*) from test')->fetchColumn();
 *
 * @package Bdf\Prime\Sharding
 */
class ShardingConnection extends SimpleConnection implements SubConnectionManagerInterface
{
    /**
     * All shard connections
     *
     * @var SimpleConnection[]
     */
    private $connections = [];
    
    /**
     * The shard choser
     * 
     * @var ShardChoserInterface
     */
    private $shardChoser;

    /**
     * The id of current shard. Null means all shards
     *
     * @var string
     */
    private $currentShardId;

    /**
     * The distribution key
     *
     * @var string
     */
    private $distributionKey;

    /**
     * Initializes a new instance of the Connection class.
     * 
     * Here's a shard connections configuration
     * 
     * @example
     *
     * $conn = DriverManager::getConnection([
     *    'driver' => 'pdo_mysql',
     *    'user'     => 'user',
     *    'password' => 'password',
     *    'host'     => '127.0.0.1',
     *    'dbname'   => 'basename',
     *    'distributionKey' => 'id',
     *    'shards' => [
     *      '{shardId}' => [
     *        'user'     => 'shard1',
     *        'host'     => '...',
     *      ]
     *    ]
     * ]);
     *
     * @param array                              $params       The connection parameters.
     * @param \Doctrine\DBAL\Driver              $driver       The driver to use.
     * @param \Doctrine\DBAL\Configuration|null  $config       The configuration, optional.
     * @param \Doctrine\Common\EventManager|null $eventManager The event manager, optional.
     */
    public function __construct(array $params, Driver $driver, Configuration $config = null, EventManager $eventManager = null)
    {
        if (!isset($params['shard_connections'])) {
            throw new LogicException('Sharding connection needs "shard_connections" configuration in parameters');
        }
        if (!isset($params['distributionKey'])) {
            throw new LogicException('Sharding connection needs distribution key in parameters');
        }

        $this->distributionKey = $params['distributionKey'];
        $this->shardChoser = $params['shardChoser'] ?? new ModuloChoser();
        $this->connections = $params['shard_connections'];

        parent::__construct($params, $driver, $config, $eventManager);

        /** @var DefaultQueryFactory $queryFactory */
        $queryFactory = $this->factory();

        $queryFactory->alias(InsertQueryInterface::class, ShardingInsertQuery::class);
        $queryFactory->alias(KeyValueQueryInterface::class, ShardingKeyValueQuery::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase()
    {
        return null;
    }

    /**
     * Get the shard ids
     *
     * @return array
     */
    public function getShardIds()
    {
        return array_keys($this->connections);
    }

    /**
     * Get the shard choser
     *
     * @return ShardChoserInterface
     */
    public function getShardChoser()
    {
        return $this->shardChoser;
    }

    /**
     * Get the distribution key
     *
     * @return string
     */
    public function getDistributionKey()
    {
        return $this->distributionKey;
    }

    /**
     * Get the current shard
     *
     * @return string
     */
    public function getCurrentShardId()
    {
        return $this->currentShardId;
    }

    /**
     * Select a shard to use.
     *
     * @param mixed $distributionValue
     *
     * @return $this
     */
    public function pickShard($distributionValue = null)
    {
        $this->useShard(
            $distributionValue !== null
                ? $this->shardChoser->pick($distributionValue, $this)
                : null
        );

        return $this;
    }

    /**
     * Use a shard
     *
     * @param string $shardId
     *
     * @return $this
     *
     * @throws ShardingException   If the shard id is not known
     */
    public function useShard($shardId = null)
    {
        if ($shardId !== null && !isset($this->connections[$shardId])) {
            throw ShardingException::unknown($shardId);
        }

        $this->currentShardId = $shardId;

        return $this;
    }

    /**
     * Check whether the connection is using a shard
     *
     * @return boolean
     */
    public function isUsingShard()
    {
        return $this->currentShardId !== null;
    }

    /**
     * Get a shard connection by its id
     * Returns all connection if id is null
     *
     * @param null|string|int $shardId
     *
     * @return SimpleConnection[]|SimpleConnection
     *
     * @psalm-param S $shardId
     * @psalm-return (S is null ? SimpleConnection[] : SimpleConnection)
     * @template S as null|array-key
     *
     * @throws ShardingException   If the shard id is not known
     */
    public function getShardConnection($shardId = null)
    {
        if ($shardId === null) {
            return $this->connections;
        }

        if (!isset($this->connections[$shardId])) {
            throw ShardingException::unknown($shardId);
        }

        return $this->connections[$shardId];
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection($name)
    {
        return $this->getShardConnection($name);
    }

    /**
     * Get the selected shards
     *
     * @return SimpleConnection[]
     */
    protected function getSelectedShards()
    {
        if ($this->isUsingShard()) {
            return [$this->connections[$this->currentShardId]];
        }

        return $this->connections;
    }

    /**
     * Get the selected shard
     *
     * @return SimpleConnection
     */
    protected function getSelectedShard()
    {
        return $this->connections[$this->currentShardId];
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        parent::close();

        $this->currentShardId = null;

        foreach ($this->connections as $shard) {
            $shard->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function builder(PreprocessorInterface $preprocessor = null)
    {
        return $this->factory()->make(ShardingQuery::class, $preprocessor);
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery(string $sql, array $params = [], $types = [], QueryCacheProfile $qcp = null): Result
    {
        if ($this->isUsingShard()) {
            return $this->getSelectedShard()->executeQuery($sql, $params, $types, $qcp);
        }

        $result = new MultiResult();

        foreach ($this->getSelectedShards() as $shard) {
            $result->add($shard->executeQuery($sql, $params, $types, $qcp));
        }

        return new Result($result, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function executeStatement($sql, array $params = [], array $types = []): int
    {
        $result = 0;

        foreach ($this->getSelectedShards() as $shard) {
            $result += $shard->executeStatement($sql, $params, $types);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): bool
    {
        $success = true;

        foreach ($this->getSelectedShards() as $shard) {
            if (!$shard->beginTransaction()) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        $success = true;

        foreach ($this->getSelectedShards() as $shard) {
            if (!$shard->commit()) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack(): bool
    {
        $success = true;

        foreach ($this->getSelectedShards() as $shard) {
            if (!$shard->rollBack()) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function createSavepoint($savepoint)
    {
        foreach ($this->getSelectedShards() as $shard) {
            $shard->createSavepoint($savepoint);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function releaseSavepoint($savepoint)
    {
        foreach ($this->getSelectedShards() as $shard) {
            $shard->releaseSavepoint($savepoint);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackSavepoint($savepoint)
    {
        foreach ($this->getSelectedShards() as $shard) {
            $shard->rollbackSavepoint($savepoint);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($seqName = null)
    {
        if ($this->isUsingShard()) {
            return $this->getSelectedShard()->lastInsertId($seqName);
        }

        // TODO doit on lever une exception ?
        return parent::lastInsertId($seqName);
    }

    /**
     * {@inheritdoc}
     */
    public function getWrappedConnection()
    {
        if ($this->isUsingShard()) {
            return $this->getSelectedShard()->getWrappedConnection();
        }

        // TODO doit on lever une exception ?
        return parent::getWrappedConnection();
    }
}
