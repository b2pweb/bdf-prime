<?php

namespace Bdf\Prime\Sharding\Query;

use Bdf\Prime\Cache\ArrayCache;
use Bdf\Prime\Cache\CacheKey;
use Bdf\Prime\Cache\CachePoolAdapter;
use Bdf\Prime\Cache\SimpleCacheAdapter;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\Connection\Factory\ShardingConnectionFactory;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Builder\TypesHelperTableBuilder;
use Bdf\Prime\Sharding\ShardingConnection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

class ShardingKeyValueQueryTest extends TestCase
{
    use PrimeTestCase;

    /** @var ConnectionManager $connections */
    protected $connections;

    /**
     * @var ShardingConnection
     */
    private $connection;

    /**
     * @var ShardingConnectionFactory
     */
    private $factory;
    /**
     * @var ConnectionRegistry
     */
    private $registry;

    /**
     * @var ConnectionInterface
     */
    private $shard1;

    /**
     * @var ConnectionInterface
     */
    private $shard2;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $configMap = [
            'sharding' => [
                'adapter'           => 'sqlite',
                'memory'            => true,
                'dbname'            => 'TEST',
                'distributionKey'   => 'id',
                'shards'    => [
                    'shard1' => ['dbname'  => 'TEST_SHARD1'],
                    'shard2' => ['dbname'  => 'TEST_SHARD2'],
                ]
            ],
        ];

        $this->factory = new ShardingConnectionFactory(new ConnectionFactory());
        $this->registry = new ConnectionRegistry($configMap, $this->factory);
        $this->connections = new ConnectionManager($this->registry);
        $this->connection = $this->connections->getConnection('sharding');

        $this->shard1 = $this->connection->getShardConnection('shard1');
        $this->shard2 = $this->connection->getShardConnection('shard2');

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
    public function test_all()
    {
        $this->connection->insert('test', ['id' => 1, 'name' => 'John']);
        $this->connection->insert('test', ['id' => 2, 'name' => 'Bob']);
        $this->connection->insert('test', ['id' => 3, 'name' => 'Bill']);

        $this->assertEquals([
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 1, 'name' => 'John'],
            ['id' => 3, 'name' => 'Bill'],
        ], $this->query()->all());
    }

    /**
     *
     */
    public function test_filter_not_on_discriminator()
    {
        $this->connection->insert('test2', ['id' => 1, 'value' => 'John', 'other' => 'b']);
        $this->connection->insert('test2', ['id' => 2, 'value' => 'Bob', 'other' => 'a']);
        $this->connection->insert('test2', ['id' => 3, 'value' => 'Bill', 'other' => 'a']);

        $this->assertEquals([
            ['id' => 2, 'value' => 'Bob', 'other' => 'a'],
            ['id' => 3, 'value' => 'Bill', 'other' => 'a'],
        ], $this->query()->from('test2')->where('other', 'a')->all());
    }

    /**
     *
     */
    public function test_filter_on_discriminator()
    {
        $this->connection->insert('test', ['id' => 1, 'name' => 'John']);
        $this->connection->insert('test', ['id' => 2, 'name' => 'Bob']);
        $this->connection->insert('test', ['id' => 3, 'name' => 'Bill']);

        $this->assertEquals([
            ['id' => 1, 'name' => 'John'],
        ], $this->query()->where('id', 1)->all());
    }

    /**
     *
     */
    public function test_limit()
    {
        $this->connection->insert('test', ['id' => 1, 'name' => 'John']);
        $this->connection->insert('test', ['id' => 2, 'name' => 'Bob']);
        $this->connection->insert('test', ['id' => 3, 'name' => 'Bill']);

        $this->assertEquals([
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 1, 'name' => 'John'],
        ], $this->query()->limit(2)->all());
    }

    /**
     *
     */
    public function test_limit_higher_than_result_size()
    {
        $this->connection->insert('test', ['id' => 1, 'name' => 'John']);
        $this->connection->insert('test', ['id' => 2, 'name' => 'Bob']);
        $this->connection->insert('test', ['id' => 3, 'name' => 'Bill']);

        $this->assertEquals([
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 1, 'name' => 'John'],
            ['id' => 3, 'name' => 'Bill'],
        ], $this->query()->limit(100)->all());
    }

    /**
     *
     */
    public function test_limit_reached_by_one_shard()
    {
        $this->connection->insert('test', ['id' => 1, 'name' => 'John']);
        $this->connection->insert('test', ['id' => 2, 'name' => 'Bob']);
        $this->connection->insert('test', ['id' => 3, 'name' => 'Bill']);

        $this->assertEquals([
            ['id' => 2, 'name' => 'Bob'],
        ], $this->query()->limit(1)->all());
    }

    /**
     *
     */
    public function test_project()
    {
        $this->connection->insert('test2', ['id' => 1, 'value' => 'John', 'other' => 'b']);
        $this->connection->insert('test2', ['id' => 2, 'value' => 'Bob', 'other' => 'a']);
        $this->connection->insert('test2', ['id' => 3, 'value' => 'Bill', 'other' => 'a']);

        $this->assertEquals([
            ['id' => 2, 'value' => 'Bob'],
            ['id' => 1, 'value' => 'John'],
            ['id' => 3, 'value' => 'Bill'],
        ], $this->query()->from('test2')->project(['id', 'value'])->all());
    }

    /**
     *
     */
    public function test_reuse_filter_on_discriminator()
    {
        $this->connection->insert('test', ['id' => 1, 'name' => 'John']);
        $this->connection->insert('test', ['id' => 2, 'name' => 'Bob']);
        $this->connection->insert('test', ['id' => 3, 'name' => 'Bill']);

        $query = $this->query();

        $this->assertEquals([['id' => 1, 'name' => 'John']], $query->where('id', 1)->all());
        $this->assertEquals([['id' => 2, 'name' => 'Bob']], $query->where('id', 2)->all());
        $this->assertEquals([['id' => 3, 'name' => 'Bill']], $query->where('id', 3)->all());
    }

    /**
     *
     */
    public function test_reuse_filter_not_on_discriminator()
    {
        $this->connection->insert('test2', ['id' => 1, 'value' => 'John', 'other' => 'b']);
        $this->connection->insert('test2', ['id' => 2, 'value' => 'Bob', 'other' => 'a']);
        $this->connection->insert('test2', ['id' => 3, 'value' => 'Bill', 'other' => 'a']);

        $query = $this->query()->from('test2');

        $this->assertEquals([
            ['id' => 2, 'value' => 'Bob', 'other' => 'a'],
            ['id' => 3, 'value' => 'Bill', 'other' => 'a'],
        ], $query->where('other', 'a')->all());

        $this->assertEquals([
            ['id' => 1, 'value' => 'John', 'other' => 'b'],
        ], $query->where('other', 'b')->all());
    }

    /**
     *
     */
    public function test_aggregate()
    {
        $this->connection->insert('test2', ['id' => 1, 'value' => 42]);
        $this->connection->insert('test2', ['id' => 2, 'value' => 666]);
        $this->connection->insert('test2', ['id' => 3, 'value' => 13]);

        $query = $this->query()->from('test2');

        $this->assertEquals(3, $query->count());
        $this->assertEquals(666, $query->max('value'));
        $this->assertEquals(13, $query->min('value'));
        //$this->assertEquals(240.333, $query->avg('value')); // Not real AVG computing : cannot be done with sharding on one query
        $this->assertEquals(721, $query->sum('value'));
    }

    /**
     *
     */
    public function test_delete_filter_by_discriminator()
    {
        $this->connection->insert('test2', ['id' => 1, 'value' => 'John', 'other' => 'b']);
        $this->connection->insert('test2', ['id' => 2, 'value' => 'Bob', 'other' => 'a']);
        $this->connection->insert('test2', ['id' => 3, 'value' => 'Bill', 'other' => 'a']);

        $query = $this->query()->from('test2');

        $this->assertEquals(1, $query->where('id', 2)->delete());
        $this->assertCount(0, $this->shard1->from('test2')->all());
        $this->assertCount(2, $this->shard2->from('test2')->all());

        $this->assertEquals(0, $query->where('id', 2)->delete());

        $this->assertEquals(1, $query->where('id', 1)->delete());
        $this->assertCount(0, $this->shard1->from('test2')->all());
        $this->assertCount(1, $this->shard2->from('test2')->all());
        $this->assertEquals([['id' => 3, 'value' => 'Bill', 'other' => 'a']], $this->shard2->from('test2')->all());
    }

    /**
     *
     */
    public function test_delete_filter_not_discriminator()
    {
        $this->connection->insert('test2', ['id' => 1, 'value' => 'John', 'other' => 'b']);
        $this->connection->insert('test2', ['id' => 2, 'value' => 'Bob', 'other' => 'a']);
        $this->connection->insert('test2', ['id' => 3, 'value' => 'Bill', 'other' => 'a']);

        $query = $this->query()->from('test2');

        $this->assertEquals(2, $query->where('other', 'a')->delete());
        $this->assertCount(0, $this->shard1->from('test2')->all());
        $this->assertCount(1, $this->shard2->from('test2')->all());

        $this->assertEquals(1, $query->where('other', 'b')->delete());
        $this->assertCount(0, $this->shard1->from('test2')->all());
        $this->assertCount(0, $this->shard2->from('test2')->all());

        $this->assertEquals(0, $query->where('other', 'c')->delete());
    }

    /**
     *
     */
    public function test_update_by_discriminator()
    {
        $this->connection->insert('test2', ['id' => 1, 'value' => 'John', 'other' => 'b']);
        $this->connection->insert('test2', ['id' => 2, 'value' => 'Bob', 'other' => 'a']);
        $this->connection->insert('test2', ['id' => 3, 'value' => 'Bill', 'other' => 'a']);

        $query = $this->query()->from('test2');

        $this->assertEquals(1, $query->where('id', 1)->values(['other' => 'c'])->update());
        $this->assertEquals('c', $this->shard2->from('test2')->where('id', 1)->inRow('other'));

        $this->assertEquals(1, $query->where('id', 2)->values(['other' => 'd'])->update());
        $this->assertEquals('d', $this->shard1->from('test2')->where('id', 2)->inRow('other'));
    }

    /**
     *
     */
    public function test_update_not_discriminator()
    {
        $this->connection->insert('test2', ['id' => 1, 'value' => 'John', 'other' => 'b']);
        $this->connection->insert('test2', ['id' => 2, 'value' => 'Bob', 'other' => 'a']);
        $this->connection->insert('test2', ['id' => 3, 'value' => 'Bill', 'other' => 'a']);

        $query = $this->query()->from('test2');

        $this->assertEquals(2, $query->where('other', 'a')->values(['other' => 'c'])->update());
        $this->assertEquals([
            ['id' => 2, 'other' => 'c'],
            ['id' => 1, 'other' => 'b'],
            ['id' => 3, 'other' => 'c'],
        ], $this->connection->from('test2')->project(['id', 'other'])->all());
    }

    /**
     *
     */
    public function test_update_will_clear_cache()
    {
        $this->connection->insert('test', ['id' => 1, 'name' => 'John']);
        $this->connection->insert('test', ['id' => 2, 'name' => 'Bob']);
        $this->connection->insert('test', ['id' => 3, 'name' => 'Bill']);

        $cache = new CachePoolAdapter(new ArrayAdapter());

        $this->query()->setCache($cache);

        $this->connection->from('test')->setCache($cache)->useCache()->all();
        $this->assertNotNull($cache->get(new CacheKey('sharding:test', sha1('SELECT * FROM test-a:0:{}'))));

        $this->query()->setCache($cache)->useCache()->where('id', 1)->update(['name' => 'Richard']);
        $this->assertNull($cache->get(new CacheKey('sharding:test', sha1('SELECT * FROM test-a:0:{}'))));
    }

    /**
     *
     */
    public function test_delete_will_clear_cache()
    {
        $this->connection->insert('test', ['id' => 1, 'name' => 'John']);
        $this->connection->insert('test', ['id' => 2, 'name' => 'Bob']);
        $this->connection->insert('test', ['id' => 3, 'name' => 'Bill']);

        $cache = new SimpleCacheAdapter(new Psr16Cache(new ArrayAdapter()));

        $this->query()->setCache($cache);

        $this->connection->from('test')->setCache($cache)->useCache()->all();
        $this->assertNotNull($cache->get(new CacheKey('sharding:test', sha1('SELECT * FROM test-a:0:{}'))));

        $this->query()->setCache($cache)->useCache()->where('id', 1)->delete();
        $this->assertNull($cache->get(new CacheKey('sharding:test', sha1('SELECT * FROM test-a:0:{}'))));
    }

    /**
     *
     */
    public function test_update_should_not_clear_cache_on_unaffected_rows()
    {
        $this->connection->insert('test', ['id' => 1, 'name' => 'John']);
        $this->connection->insert('test', ['id' => 2, 'name' => 'Bob']);
        $this->connection->insert('test', ['id' => 3, 'name' => 'Bill']);

        $cache = new ArrayCache();

        $this->query()->setCache($cache);

        $this->connection->from('test')->setCache($cache)->useCache()->all();
        $this->assertNotNull($cache->get(new CacheKey('sharding:test', sha1('SELECT * FROM test-a:0:{}'))));

        $this->query()->setCache($cache)->where('id', 42)->useCache()->update(['name' => 'Richard']);
        $this->assertNotNull($cache->get(new CacheKey('sharding:test', sha1('SELECT * FROM test-a:0:{}'))));
    }

    /**
     *
     */
    public function test_delete_should_not_clear_cache_on_unaffected_rows()
    {
        $this->connection->insert('test', ['id' => 1, 'name' => 'John']);
        $this->connection->insert('test', ['id' => 2, 'name' => 'Bob']);
        $this->connection->insert('test', ['id' => 3, 'name' => 'Bill']);

        $cache = new ArrayCache();

        $this->query()->setCache($cache);

        $this->connection->from('test')->setCache($cache)->useCache()->all();
        $this->assertNotNull($cache->get(new CacheKey('sharding:test', sha1('SELECT * FROM test-a:0:{}'))));

        $this->query()->setCache($cache)->useCache()->where('id', 42)->delete();
        $this->assertNotNull($cache->get(new CacheKey('sharding:test', sha1('SELECT * FROM test-a:0:{}'))));
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
    public function test_pick_shard()
    {
        $this->connection->insert('test', ['id' => 1, 'name' => 'John']);

        $this->assertSame(1, $this->query()->pickShard(1)->count());
        $this->assertSame(0, $this->query()->pickShard(2)->count());
        $this->assertSame(0, $this->connection->getShardConnection('shard1')->from('test')->count());
        $this->assertSame(1, $this->connection->getShardConnection('shard2')->from('test')->count());
    }

    /**
     * @return ShardingKeyValueQuery
     */
    private function query()
    {
        return $this->connection->make(ShardingKeyValueQuery::class)->from('test');
    }
}
