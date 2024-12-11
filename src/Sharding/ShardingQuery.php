<?php

namespace Bdf\Prime\Sharding;

use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\Query;
use Bdf\Prime\Sharding\Extension\ShardPicker;

use function array_sum;
use function count;

/**
 * ShardingQuery
 *
 * @template R as object|array
 *
 * @property ShardingConnection $connection protected
 * @template R as object|array
 *
 * @extends Query<ShardingConnection, R>
 */
class ShardingQuery extends Query
{
    use ShardPicker;

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function execute($columns = null): ResultSetInterface
    {
        $lastShard = $this->connection->getCurrentShardId();

        try {
            if (!$this->shardId && $this->statements['where']) {
                $this->explodeQueryClauses($this->statements['where'], $this->connection->getDistributionKey());
            } else {
                $this->connection->useShard($this->shardId);
            }

            return parent::execute($columns);
        } finally {
            $this->connection->useShard($lastShard);
        }
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    protected function executeUpdate(string $type): int
    {
        $lastShard = $this->connection->getCurrentShardId();

        try {
            $distributionKey = $this->connection->getDistributionKey();

            if ($this->shardId) {
                $this->connection->useShard($this->shardId);
            } elseif (isset($this->statements['values']['data'][$distributionKey])) {
                $this->connection->pickShard($this->statements['values']['data'][$distributionKey]);
            } elseif ($this->statements['where']) {
                $this->explodeQueryClauses($this->statements['where'], $distributionKey);
            } else {
                // To keep the old behavior : use $this->shardId instead of null constant
                // because falsy comparison is used instead of strict null comparison, so $this->shardId can be 0 or "0"
                // which has a different meaning than null
                $this->connection->useShard($this->shardId);
            }

            return parent::executeUpdate($type);
        } finally {
            $this->connection->useShard($lastShard);
        }
    }

    /**
     * Explore clauses of a query to find the distribution value
     *
     * @param array  $clauses
     * @param string $distributionKey
     *
     * @return boolean Returns true if a shard has been selected
     */
    private function explodeQueryClauses(array $clauses, $distributionKey)
    {
        foreach ($clauses as $clause) {
            if (isset($clause['nested'])) {
                if ($this->explodeQueryClauses($clause['nested'], $distributionKey) === true) {
                    return true;
                }
            } elseif (isset($clause['column']) && $clause['column'] === $distributionKey) {
                switch ($clause['operator']) {
                    case '=':
                    case ':eq':
                        $this->connection->pickShard($clause['value']);
                        return true;
                }
            }
        }

        $this->connection->pickShard();

        return false;
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function paginationCount(?string $column = null): int
    {
        $statements = $this->statements;

        $this->compilerState->invalidate(['columns', 'orders']);

        $this->statements['orders'] = [];
        $this->statements['limit'] = null;
        $this->statements['offset'] = null;
        $this->statements['aggregate'] = ['pagination', $this->getPaginationColumns($column)];

        $count = 0;

        foreach ($this->execute() as $result) {
            $count += (int)$result['aggregate'];
        }

        $this->compilerState->invalidate(['columns', 'orders']);
        $this->statements = $statements;

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function count(?string $column = null): int
    {
        return (int)array_sum($this->aggregate(__FUNCTION__, $column));
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function avg(?string $column = null): float
    {
        $numbers = $this->aggregate(__FUNCTION__, $column);

        return array_sum($numbers) / count($numbers);
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
        return (float)array_sum($this->aggregate(__FUNCTION__, $column));
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function aggregate(string $function, ?string $column = null)
    {
        $statements = $this->statements;

        $this->compilerState->invalidate('columns');

        $this->statements['aggregate'] = [$function, $column ?: '*'];

        $aggregate = [];

        foreach ($this->execute() as $result) {
            $aggregate[] = $result['aggregate'];
        }

        $this->compilerState->invalidate('columns');
        $this->statements = $statements;

        return $aggregate;
    }
}
