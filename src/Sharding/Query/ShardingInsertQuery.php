<?php

namespace Bdf\Prime\Sharding\Query;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\DefaultPreprocessor;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Cachable;
use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use Bdf\Prime\Query\Custom\BulkInsert\BulkInsertQuery;
use Bdf\Prime\Query\Extension\CachableTrait;
use Bdf\Prime\Sharding\ShardingConnection;

/**
 * Handle INSERT operations on Sharding connection set
 * The shard will be choosed using inserted data value, and the operation will be delegated to the shard connection
 */
class ShardingInsertQuery extends CompilableClause implements InsertQueryInterface, CommandInterface, Cachable
{
    use CachableTrait;

    /**
     * The DBAL Connection.
     *
     * @var ShardingConnection
     */
    private $connection;

    /**
     * @var string
     */
    private $table;

    /**
     * @var string[]
     */
    private $columns = [];

    /**
     * @var string
     */
    private $mode = self::MODE_INSERT;

    /**
     * @var array
     */
    private $values = [];

    /**
     * Queries indexed by shard id
     *
     * @var BulkInsertQuery[]
     */
    private $queries = [];

    /**
     * @var BulkInsertQuery
     */
    private $currentQuery;


    /**
     * ShardingInsertQuery constructor.
     *
     * @param ShardingConnection $connection
     * @param PreprocessorInterface $preprocessor
     */
    public function __construct(ShardingConnection $connection, PreprocessorInterface $preprocessor = null)
    {
        parent::__construct($preprocessor ?: new DefaultPreprocessor());

        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function compiler() { }

    /**
     * {@inheritdoc}
     */
    public function setCompiler(CompilerInterface $compiler) { }

    /**
     * {@inheritdoc}
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function on(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->queries = [];
        $this->currentQuery = null;
    }

    /**
     * {@inheritdoc}
     */
    public function from($from, $alias = null)
    {
        return $this->into($from);
    }

    /**
     * {@inheritdoc}
     */
    public function bulk($flag = true)
    {
        throw new \BadMethodCallException('Bulk insert is not (yet ?) supported by sharding connection');
    }

    /**
     * {@inheritdoc}
     */
    public function into($table)
    {
        $this->table = $table;

        foreach ($this->queries as $query) {
            $query->into($table);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function columns(array $columns)
    {
        $this->columns = $columns;

        foreach ($this->queries as $query) {
            $query->columns($columns);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function values(array $data, $replace = false)
    {
        $this->values = $data;
        $this->currentQuery = null;

        $this->currentQuery()->values($data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function mode($mode)
    {
        $this->mode = $mode;

        foreach ($this->queries as $query) {
            $query->mode($mode);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ignore($flag = true)
    {
        return $this->mode($flag ? self::MODE_IGNORE : self::MODE_INSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function replace($flag = true)
    {
        return $this->mode($flag ? self::MODE_REPLACE : self::MODE_INSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($columns = null)
    {
        $count = $this->currentQuery()->execute($columns);

        if ($count > 0) {
            $this->clearCacheOnWrite();
        }

        return $count;
    }

    /**
     * Get the query associated to the matching sharding
     *
     * @return BulkInsertQuery
     */
    private function currentQuery()
    {
        if ($this->currentQuery !== null) {
            return $this->currentQuery;
        }

        $distributionKey = $this->connection->getDistributionKey();

        if (!isset($this->values[$distributionKey])) {
            throw new \LogicException('The value "'.$distributionKey.'" must be provided for selecting the sharding');
        }

        $shardId = $this->connection->getShardChoser()->pick($this->values[$distributionKey], $this->connection);

        if (isset($this->queries[$shardId])) {
            return $this->queries[$shardId];
        }

        /** @var BulkInsertQuery $query */
        $query = $this->connection->getConnection($shardId)->make(BulkInsertQuery::class, $this->preprocessor());

        $query
            ->setCache($this->cache)
            ->into($this->table)
            ->columns($this->columns)
            ->mode($this->mode)
        ;

        return $this->currentQuery = $this->queries[$shardId] = $query;
    }

    /**
     * {@inheritdoc}
     */
    protected function cacheNamespace()
    {
        return $this->connection->getName().':'.$this->table;
    }
}
