<?php

namespace Bdf\Prime\Sharding;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ModuloChoserTest extends TestCase
{
    /**
     *
     */
    public function test_pick()
    {
        $choser = new ModuloChoser();

        $connection = $this->getMockBuilder(ShardingConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShardIds'])
            ->getMock();
        $connection->expects($this->any())->method('getShardIds')->willReturn(['shard1', 'shard2']);

        $this->assertEquals('shard2', $choser->pick(1, $connection));
        $this->assertEquals('shard1', $choser->pick(0, $connection));
        $this->assertEquals('shard1', $choser->pick(2, $connection));
    }
}