<?php

namespace Bdf\Prime\Sharding\Query;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ArrayResultSet;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Exception\ShardingException;
use Bdf\Prime\Query\AbstractReadCommand;
use Bdf\Prime\Query\Compiler\Preprocessor\DefaultPreprocessor;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\Extension\CachableTrait;
use Bdf\Prime\Query\Extension\ExecutableTrait;
use Bdf\Prime\Sharding\Extension\ShardPicker;
use Bdf\Prime\Sharding\ShardingConnection;

/**
 * Handle simple key/value query on sharding connection
 * If the distribution key is found on the filters, the corresponding sharding query is used
 * In other case, all shards will be queried on
 *
 * @property ShardingConnection $connection
 *
 * @template R as object|array
 *
 * @implements KeyValueQueryInterface<ShardingConnection, R>
 * @extends AbstractReadCommand<ShardingConnection, R>
 */
class ShardingKeyValueQuery extends AbstractReadCommand implements KeyValueQueryInterface
{
    use CachableTrait;
    use ExecutableTrait;
    use ShardPicker;

    /**
     * @var KeyValueQueryInterface[]
     */
    private $queries = [];


    /**
     * ShardingKeyValueQuery constructor.
     *
     * @param ShardingConnection $connection
     * @param PreprocessorInterface|null $preprocessor
     */
    public function __construct(ShardingConnection $connection, ?PreprocessorInterface $preprocessor = null)
    {
        parent::__construct($connection, $preprocessor ?: new DefaultPreprocessor());

        $this->statements = [
            'where'     => [],
            'table'     => null,
            'columns'   => [],
            'aggregate' => null,
            'limit'     => null,
            'values'    => [
                'data'  => [],
                'types' => []
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function on(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->queries    = [];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function project($columns = null)
    {
        $this->statements['columns'] = (array) $columns;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function select($columns = null)
    {
        return $this->select($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function addSelect($columns)
    {
        $this->statements['columns'] = array_merge($this->statements['columns'], $columns);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function from($from, $alias = null)
    {
        $this->statements['table'] = $from;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function where($field, $value = null)
    {
        if (is_array($field)) {
            $this->statements['where'] = $field + $this->statements['where'];
        } else {
            $this->statements['where'][$field] = $value;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function values(array $values = [], array $types = [])
    {
        $this->statements['values'] = [
            'data'  => $values,
            'types' => $types,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @internal Use internally for optimise "first" query. The offset parameter is not used
     *
     * @return static<R>
     */
    public function limit(?int $limit, ?int $offset = null)
    {
        $this->statements['limit'] = $limit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function count(?string $column = null): int
    {
        return (int) array_sum($this->aggregate(__FUNCTION__, $column));
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function avg(?string $column = null): float
    {
        $results = $this->aggregate(__FUNCTION__, $column);

        return (float) array_sum($results) / count($results);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function min(?string $column = null)
    {
        return min($this->aggregate(__FUNCTION__, $column));
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function max(?string $column = null)
    {
        return max($this->aggregate(__FUNCTION__, $column));
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function sum(?string $column = null): float
    {
        return (float) array_sum($this->aggregate(__FUNCTION__, $column));
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    #[ReadOperation]
    public function aggregate(string $function, ?string $column = null): array
    {
        $results = [];

        foreach ($this->selectQueries() as $query) {
            $results[] = $query->aggregate($function, $column);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     *
     * @todo execute cached with closure
     */
    #[ReadOperation]
    public function execute($columns = null): ResultSetInterface
    {
        $results = [];
        $limit = $this->statements['limit'];

        foreach ($this->selectQueries() as $query) {
            $results = array_merge($results, $query->execute($columns)->all());

            if ($limit) {
                $count = count($results);

                if ($count == $limit) {
                    break;
                }

                if ($count > $limit) {
                    $results = array_slice($results, 0, $limit);
                    break;
                }
            }
        }

        return new ArrayResultSet($results);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function update($values = null): int
    {
        $count = 0;

        foreach ($this->selectQueries() as $query) {
            $count += $query->update($values);
        }

        if ($count > 0) {
            $this->clearCacheOnWrite();
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function delete(): int
    {
        $count = 0;

        foreach ($this->selectQueries() as $query) {
            $count += $query->delete();
        }

        if ($count > 0) {
            $this->clearCacheOnWrite();
        }

        return $count;
    }

    /**
     * Select the queries to use
     *
     * @return iterable<KeyValueQueryInterface>
     *
     * @throws ShardingException
     */
    private function selectQueries(): iterable
    {
        foreach ($this->getShardIds() as $shardId) {
            yield $this->getQueryByShard($shardId);
        }
    }

    /**
     * Get the targeted shard IDs
     *
     * @return list<string>
     */
    private function getShardIds(): array
    {
        if ($this->shardId !== null) {
            return [$this->shardId];
        }

        $distributionKey = $this->connection->getDistributionKey();

        if (isset($this->statements['where'][$distributionKey])) {
            return [$this->connection->getShardChoser()->pick($this->statements['where'][$distributionKey], $this->connection)];
        }

        return $this->connection->getShardIds();
    }

    /**
     * Get and configure a query for the given shard
     *
     * @param string $shardId The shard id
     *
     * @return KeyValueQueryInterface
     *
     * @throws ShardingException
     */
    private function getQueryByShard($shardId)
    {
        if (isset($this->queries[$shardId])) {
            $query = $this->queries[$shardId];
            /** @var KeyValueQueryInterface $query */
        } else {
            $this->queries[$shardId] = $query = $this->connection->getShardConnection($shardId)->make(KeyValueQueryInterface::class, $this->preprocessor());
            /** @var KeyValueQueryInterface $query */
            $query->setExtension($this->extension);
        }

        $query->from($this->statements['table']);

        if (!empty($this->statements['limit'])) {
            /** @var KeyValueQueryInterface&\Bdf\Prime\Query\Contract\Limitable $query */
            $query->limit($this->statements['limit']);
        }

        if (!empty($this->statements['columns'])) {
            $query->project($this->statements['columns']);
        }

        if (!empty($this->statements['where'])) {
            $query->where($this->statements['where']);
        }

        if (!empty($this->statements['values']['data'])) {
            $query->values($this->statements['values']['data'], $this->statements['values']['types']);
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    protected function cacheNamespace(): string
    {
        return $this->connection->getName().':'.$this->statements['table'];
    }
}
