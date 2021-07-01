<?php

namespace Bdf\Prime\Connection\Factory;

use Bdf\Prime\Configuration;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Sharding\ShardingConnection;

/**
 * ShardingConnection
 */
class ShardingConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * The delegated loader
     *
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * Set default configuration
     *
     * @param ConnectionFactoryInterface $connectionFactory
     */
    public function __construct(ConnectionFactoryInterface $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $connectionName, array $parameters, ?Configuration $config = null): ConnectionInterface
    {
        $allParameters = $parameters['shards'];
        unset($parameters['shards']);

        $globalParameters = $parameters;
        unset($globalParameters['wrapperClass']);
        unset($globalParameters['distributionKey']);
        unset($globalParameters['shardChoser']);

        $parameters['shard_connections'] = [];
        foreach ($allParameters as $shardId => $shardParameters) {
            $parameters['shard_connections'][$shardId] = $this->connectionFactory->create($connectionName.'.'.$shardId, array_merge($globalParameters, $shardParameters), $config);
        }

        $parameters['wrapperClass'] = $parameters['wrapperClass'] ?? ShardingConnection::class;

        return $this->connectionFactory->create($connectionName, $parameters, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function support(string $connectionName, array $parameters): bool
    {
        return isset($parameters['shards']);
    }
}
