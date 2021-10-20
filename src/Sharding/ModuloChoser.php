<?php

namespace Bdf\Prime\Sharding;

/**
 * Select a shard by modulo of the distribution value
 * The discriminator value must be a numeric value
 *
 * @implements ShardChoserInterface<int>
 */
class ModuloChoser implements ShardChoserInterface
{
    /**
     * {@inheritdoc}
     */
    public function pick($distributionValue, ShardingConnection $connection): string
    {
        $ids = $connection->getShardIds();

        return $ids[$distributionValue % count($ids)];
    }
}
