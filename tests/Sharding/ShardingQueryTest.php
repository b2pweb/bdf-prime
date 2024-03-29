<?php

namespace Bdf\Prime\Sharding;

use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Builder\TypesHelperTableBuilder;
use Doctrine\DBAL\Logging\DebugStack;
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
     *
     */
    public function test_dont_pick_shard()
    {
        $query = $this->query();
        $query->insert(['id' => 1, 'name' => 'John']);

        $this->assertSame(1, $this->query()->pickShard(1)->count());
        $this->assertSame(0, $this->query()->pickShard(2)->count());
        $this->assertSame(0, $this->connection->getShardConnection('shard1')->from('test')->count());
        $this->assertSame(1, $this->connection->getShardConnection('shard2')->from('test')->count());
    }

    /**
     *
     */
    public function test_pick_same_shard()
    {
        $query = $this->query();
        $query->pickShard(1);
        $query->insert(['id' => 1, 'name' => 'John']);

        $this->assertSame(1, $this->query()->pickShard(1)->count());
        $this->assertSame(0, $this->query()->pickShard(2)->count());
        $this->assertSame(0, $this->connection->getShardConnection('shard1')->from('test')->count());
        $this->assertSame(1, $this->connection->getShardConnection('shard2')->from('test')->count());
    }

    /**
     *
     */
    public function test_pick_shard()
    {
        $query = $this->query();
        $query->pickShard(2);
        $query->insert(['id' => 1, 'name' => 'John']);

        $this->assertSame(0, $this->query()->pickShard(1)->count());
        $this->assertSame(1, $this->query()->pickShard(2)->count());
        $this->assertSame(1, $this->connection->getShardConnection('shard1')->from('test')->count());
        $this->assertSame(0, $this->connection->getShardConnection('shard2')->from('test')->count());
    }

    public function test_update_should_use_where_to_pick_shard()
    {
        $this->connection->getConfiguration()->setSQLLogger($logger = new DebugStack());

        $this->query()->insert(['id' => 1, 'name' => 'John']);
        $this->query()->insert(['id' => 2, 'name' => 'Mike']);

        $this->assertCount(2, $logger->queries);

        $this->query()->where('id', 1)->update(['name' => 'Jean']);
        $this->assertCount(3, $logger->queries);

        $this->assertSame('Jean', $this->query()->pickShard(1)->first()['name']);
    }

    public function test_update_should_priorize_data_to_where()
    {
        $this->query()->insert(['id' => 1, 'name' => 'John']);
        $this->query()->insert(['id' => 2, 'name' => 'Mike']);

        $this->query()->where('id', 1)->update(['id' => 4]);

        $this->assertEquals([
            ['id' => 2, 'name' => 'Mike'],
            ['id' => 1, 'name' => 'John'],
        ], $this->query()->all());
    }

    /**
     * @return ShardingQuery
     */
    private function query()
    {
        return $this->connection->make(ShardingQuery::class)->from('test');
    }
}
