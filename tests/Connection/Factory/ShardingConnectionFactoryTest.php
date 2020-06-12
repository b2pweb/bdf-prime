<?php

namespace Bdf\Prime\Connection\Factory;

use Bdf\Prime\Configuration;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Sharding\ShardingConnection;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ShardingConnectionFactoryTest extends TestCase
{
    /**
     *
     */
    public function test_support()
    {
        $delegate = $this->createMock(ConnectionFactoryInterface::class);
        $factory = new ShardingConnectionFactory($delegate);

        $this->assertFalse($factory->support('foo', []));
        $this->assertTrue($factory->support('foo', ['shards' => []]));
    }

    /**
     *
     */
    public function test_with_sharding_parameters()
    {
        $connectionName = 'foo';
        $parameters = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'distributionKey' => 'id',
            'shards' => [
                'shard1' => ['dbname' => 'TEST_SHARD1'],
                'shard2' => ['dbname' => 'TEST_SHARD2'],
            ]
        ];
        $config = new Configuration();

        $shard1 = $this->createMock(ConnectionInterface::class);
        $shard2 = $this->createMock(ConnectionInterface::class);
        $delegate = $this->createMock(ConnectionFactoryInterface::class);
        $factory = new ShardingConnectionFactory($delegate);

        $delegate->expects($this->at(0))
            ->method('create')
            ->with($connectionName.'.shard1', ['driver' => 'pdo_sqlite', 'memory' => true, 'dbname' => 'TEST_SHARD1'], $config)
            ->willReturn($shard1);

        $delegate->expects($this->at(1))
            ->method('create')
            ->with($connectionName.'.shard2', ['driver' => 'pdo_sqlite', 'memory' => true, 'dbname' => 'TEST_SHARD2'], $config)
            ->willReturn($shard2);

        $globalParameters = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'distributionKey' => 'id',
            'shard_connections' => [
                'shard1' => $shard1,
                'shard2' => $shard2,
            ],
            'wrapperClass' => ShardingConnection::class
        ];
        $delegate->expects($this->at(2))
            ->method('create')
            ->with($connectionName, $globalParameters, $config)
            ->willReturn($this->createMock(ConnectionInterface::class));

        $factory->create($connectionName, $parameters, $config);
    }
}
