<?php

namespace Bdf\Prime\Exception;

/**
 * Exception related to sharding connections
 */
class ShardingException extends DBALException
{
    /**
     * The given shard id is unknown
     *
     * @param string $shardId
     *
     * @return static
     */
    public static function unknown(string $shardId): self
    {
        return new self('Trying to use an unknown shard id "'.$shardId.'"');
    }
}
