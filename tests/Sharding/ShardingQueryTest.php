<?php

namespace Bdf\Prime\Sharding;

use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Builder\TypesHelperTableBuilder;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ShardingQueryTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var ShardingConnection
     */
    private $connection;

    /**
     * @var SimpleConnection
     */
    private $shard1;

    /**
     * @var SimpleConnection
     */
    private $shard2;


    /**
     *
     */
    protected function setUp(): void
    {
        $this->configurePrime();

        $this->prime()->connections()->removeConnection('test');
        $this->prime()->connections()->declareConnection('test', [
            'adapter'           => 'sqlite',
            'memory'            => true,
            'dbname'            => 'TEST',
            'distributionKey'   => 'id',
            'shards'    => [
                'shard1' => ['dbname'  => 'TEST_SHARD1'],
                'shard2' => ['dbname'  => 'TEST_SHARD2'],
            ]
        ]);

        $this->connection = $this->prime()->connection('test');
        $this->shard1 = $this->prime()->connection('test')->getShardConnection('shard1');
        $this->shard2 = $this->prime()->connection('test')->getShardConnection('shard2');

        $this->connection->schema()
            ->table('test', function(TypesHelperTableBuilder $table) {
                $table->bigint('id', true)->primary();
                $table->string('name');
            })
            ->table('test2', function(TypesHelperTableBuilder $table) {
                $table->bigint('id', true)->primary();
                $table->string('value');
                $table->string('other')->nillable();
            })
        ;
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }

    /**
     *
     */
    public function test_execute_will_not_change_current_sharding()
    {
        $this->assertFalse($this->connection->isUsingShard());

        $this->query()->where('id', 5)->execute();
        $this->assertFalse($this->connection->isUsingShard());

        $this->connection->useShard('shard1');
        $this->query()->where('id', 3)->execute();
        $this->assertEquals('shard1', $this->connection->getCurrentShardId());
    }

    /**
     *
     */
    public function test_update_will_not_change_current_sharding()
    {
        $this->assertFalse($this->connection->isUsingShard());

        $this->query()->where('id', 5)->update(['name' => 'John']);
        $this->assertFalse($this->connection->isUsingShard());

        $this->connection->useShard('shard1');
        $this->query()->where('id', 3)->update(['name' => 'John']);
        $this->assertEquals('shard1', $this->connection->getCurrentShardId());
    }

    /**
     * @return ShardingQuery
     */
    private function query()
    {
        return $this->connection->make(ShardingQuery::class)->from('test');
    }
}
