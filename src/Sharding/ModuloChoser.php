<?php

namespace Bdf\Prime\Sharding;

/**
 * ModuloChoser
 *
 * select a shard by modulo of the distribution value
 *
 * @package Bdf\Prime\Sharding
 */
class ModuloChoser implements ShardChoserInterface
{
    /**
     * {@inheritdoc}
     */
    public function pick($distributionValue, ShardingConnection $connection)
    {
        $ids = $connection->getShardIds();

        return $ids[$distributionValue % count($ids)];
    }
}