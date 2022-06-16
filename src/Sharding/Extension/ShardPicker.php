<?php

namespace Bdf\Prime\Sharding\Extension;

use Bdf\Prime\Sharding\ShardingConnection;

/**
 * Trait ShardPicker
 *
 * @property ShardingConnection $connection
 */
trait ShardPicker
{
    /**
     * @var null|string
     */
    private $shardId;

    /**
     * Pick up a shard manually from the distribution value
     * This query will be executed only on the targeted shard
     *
     * @param mixed $distributionValue
     *
     * @return $this
     */
    public function pickShard($distributionValue)
    {
        $this->shardId = $this->connection->getShardChoser()
            ->pick($distributionValue, $this->connection);

        return $this;
    }

    /**
     * Set the shard manually
     * This query will be executed only on this shard
     *
     * @param null|string $shardId
     *
     * @return $this
     */
    public function useShard(?string $shardId)
    {
        $this->shardId = $shardId;

        return $this;
    }
}
