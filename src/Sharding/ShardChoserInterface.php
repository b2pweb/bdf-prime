<?php

namespace Bdf\Prime\Sharding;

/**
 * Choose a shard using a distribution value
 *
 * @template V as scalar
 */
interface ShardChoserInterface
{
    /**
     * Picks a shard for the given distribution value.
     * 
     * @param V $distributionValue The distribution value
     * @param ShardingConnection $connection The sharding connection
     *
     * @return string  Returns the shard id to use
     */
    public function pick($distributionValue, ShardingConnection $connection): string;
}
