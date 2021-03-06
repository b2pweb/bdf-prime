<?php

namespace Bdf\Prime\Sharding;

use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\Query;
use Bdf\Prime\Sharding\Extension\ShardPicker;

/**
 * ShardingQuery
 *
 * @property ShardingConnection $connection protected
 */
class ShardingQuery extends Query
{
    use ShardPicker;

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function execute($columns = null)
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
    protected function executeUpdate($type)
    {
        $lastShard = $this->connection->getCurrentShardId();

        try {
            $distributionKey = $this->connection->getDistributionKey();

            if (!$this->shardId && isset($this->statements['values']['data'][$distributionKey])) {
                $this->connection->pickShard($this->statements['values']['data'][$distributionKey]);
            } else {
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
    public function paginationCount($column = null)
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
    public function count($column = null)
    {
        return (int)array_sum($this->aggregate(__FUNCTION__, $column));
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function avg($column = null)
    {
        $numbers = $this->aggregate(__FUNCTION__, $column);

        return array_sum($numbers)/count($numbers);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function min($column = null)
    {
        return (float)min($this->aggregate(__FUNCTION__, $column));
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function max($column = null)
    {
        return (float)max($this->aggregate(__FUNCTION__, $column));
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function sum($column = null)
    {
        return (float)array_sum($this->aggregate(__FUNCTION__, $column));
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function aggregate($function, $column = null)
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
