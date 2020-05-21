<?php

namespace Bdf\Prime\Sharding\Query;

use Bdf\Prime\Cache\ArrayCache;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Builder\TypesHelperTableBuilder;
use Bdf\Prime\Sharding\ShardingConnection;
use PHPUnit\Framework\TestCase;

/**
 * Class ShardingInsertQueryTest
 */
class ShardingInsertQueryTest extends TestCase
{
    use PrimeTestCase;

    /** @var ConnectionManager $connections */
    protected $connections;

    /**
     * @var ShardingConnection
     */
    private $connection;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->connections = new ConnectionManager([
//            'logger' => new PsrDecorator(new Logger()),
            'dbConfig' => [
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
            ]
        ]);

        $this->connection = $this->connections->connection('sharding');

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
    public function test_simple_insert()
    {
        $this->assertEquals(1, $this->query()->values(['id' => 1, 'name' => 'foo'])->execute());

        $this->assertEquals([['id' => 1, 'name' => 'foo']], $this->connection->builder()->from('test')->all());

        $this->assertEquals([], $this->connection->getShardConnection('shard1')->builder()->from('test')->all());
        $this->assertEquals([['id' => 1, 'name' => 'foo']], $this->connection->getShardConnection('shard2')->builder()->from('test')->all());

        $this->assertEquals(1, $this->query()->values(['id' => 2, 'name' => 'bar'])->execute());

        $this->assertEquals([['id' => 2, 'name' => 'bar'], ['id' => 1, 'name' => 'foo']], $this->connection->builder()->from('test')->all());

        $this->assertEquals([['id' => 2, 'name' => 'bar']], $this->connection->getShardConnection('shard1')->builder()->from('test')->all());
        $this->assertEquals([['id' => 1, 'name' => 'foo']], $this->connection->getShardConnection('shard2')->builder()->from('test')->all());
    }

    /**
     *
     */
    public function test_insert_reuse()
    {
        $query = $this->query();

        $this->assertEquals(1, $query->values(['id' => 1, 'name' => 'foo'])->execute());
        $this->assertEquals(1, $query->values(['id' => 2, 'name' => 'bar'])->execute());
        $this->assertEquals(1, $query->values(['id' => 3, 'name' => 'oof'])->execute());

        $this->assertEquals([['id' => 2, 'name' => 'bar']], $this->connection->getShardConnection('shard1')->builder()->from('test')->all());
        $this->assertEquals([['id' => 1, 'name' => 'foo'], ['id' => 3, 'name' => 'oof']], $this->connection->getShardConnection('shard2')->builder()->from('test')->all());
    }

    /**
     *
     */
    public function test_insert_ignore()
    {
        $query = $this->query();

        $this->assertEquals(1, $query->values(['id' => 1, 'name' => 'foo'])->execute());
        $this->assertEquals(0, $query->values(['id' => 1, 'name' => 'oof'])->ignore()->execute());

        $this->assertEquals([['id' => 1, 'name' => 'foo']], $this->connection->getShardConnection('shard2')->builder()->from('test')->all());
    }

    /**
     *
     */
    public function test_insert_replace()
    {
        $query = $this->query();

        $this->assertEquals(1, $query->values(['id' => 1, 'name' => 'foo'])->execute());
        $this->assertEquals(1, $query->values(['id' => 1, 'name' => 'oof'])->replace()->execute());

        $this->assertEquals([['id' => 1, 'name' => 'oof']], $this->connection->getShardConnection('shard2')->builder()->from('test')->all());
    }

    /**
     *
     */
    public function test_into_should_change_table_on_prepared_queries()
    {
        $query = $this->query();

        $query->values(['id' => 1, 'name' => 'foo'])->execute();

        $this->assertEquals(1, $query->into('test2')->values(['id' => 1, 'value' => 'val', 'other' => '42'])->execute());

        $this->assertEquals([['id' => 1, 'value' => 'val', 'other' => '42']], $this->connection->getShardConnection('shard2')->builder()->from('test2')->all());
    }

    /**
     *
     */
    public function test_columns()
    {
        $query = $this->query()->into('test2');

        $query->values(['id' => 1, 'value' => 'val', 'other' => '42'])->execute();
        $query->columns(['id', 'value'])->values(['id' => 3, 'value' => 'val2', 'other' => 'az'])->execute();

        $this->assertEquals([
            ['id' => 1, 'value' => 'val', 'other' => '42'],
            ['id' => 3, 'value' => 'val2', 'other' => null],
        ], $this->connection->getShardConnection('shard2')->builder()->from('test2')->all());
    }

    /**
     *
     */
    public function test_cache()
    {
        $cache = new ArrayCache();

        $this->connection->insert('test', ['id' => 1, 'name' => 'foo']);
        $this->connection->from('test')->setCache($cache)->all();

        $this->assertNotNull($cache->get('sharding:test', sha1('SELECT * FROM test-a:0:{}')));

        $query = $this->query();

        $query
            ->setCache($cache)
            ->values(['id' => 3, 'name' => 'oof'])
            ->execute()
        ;

        $this->assertNull($cache->get('sharding:test', sha1('SELECT * FROM test-a:0:{}')));
    }

    /**
     *
     */
    public function test_missing_values()
    {
        $this->expectException(\LogicException::class);

        $this->query()->execute();
    }

    /**
     *
     */
    public function test_execute_will_not_change_current_sharding()
    {
        $this->assertFalse($this->connection->isUsingShard());

        $this->query()->values(['id' => 1, 'name' => 'John'])->execute();
        $this->assertFalse($this->connection->isUsingShard());

        $this->connection->useShard('shard1');
        $this->query()->values(['id' => 3, 'name' => 'Bob'])->execute();
        $this->assertEquals('shard1', $this->connection->getCurrentShardId());
    }

    /**
     * @return ShardingInsertQuery
     */
    private function query()
    {
        return $this->connection->make(ShardingInsertQuery::class)->into('test');
    }
}
