<?php

namespace Bdf\Prime\Sharding;

/**
 * ShardChoserInterface
 *
 * @package Bdf\Prime\Sharding
 */
interface ShardChoserInterface
{
    /**
     * Picks a shard for the given distribution value.
     * 
     * @param mixed              $distributionValue  The distribution value
     * @param ShardingConnection $connection         The sharding connection
     *
     * @return string  Returns the shard id to use
     */
    public function pick($distributionValue, ShardingConnection $connection);
}