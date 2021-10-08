<?php

namespace Bdf\Prime\Sharding;

use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\Connection\Factory\ShardingConnectionFactory;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Exception\ShardingException;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use Bdf\Prime\Schema\Builder\TypesHelperTableBuilder;
use Bdf\Prime\Sharding\Query\ShardingInsertQuery;
use Bdf\Prime\Sharding\Query\ShardingKeyValueQuery;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ShardingConnectionTest extends TestCase
{
    use PrimeTestCase;

    /** @var ConnectionManager $connections */
    protected $connections;
    
    /**
     * 
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $configMap = [
            'sharding' => 'sqlite::memory:?distributionKey=id&shards[shard1][dbname]=TEST_SHARD1&shards[shard2][dbname]=TEST_SHARD2'
        ];

        $this->factory = new ShardingConnectionFactory(new ConnectionFactory());
        $this->registry = new ConnectionRegistry($configMap, $this->factory);
        $this->connections = new ConnectionManager($this->registry);
        $this->connections->getConnection('sharding')->schema()
            ->table('test', function(TypesHelperTableBuilder $table) {
                $table->bigint('id')->autoincrement(true);
                $table->string('name');
                $table->primary('id');
            });
    }

    /**
     * 
     */
    protected function tearDown(): void
    {
        $this->primeStop();
    }

    /**
     *
     */
    public function test_wrapper()
    {
        $this->assertEquals(ShardingConnection::class, get_class($this->connections->getConnection('sharding')));
    }

    /**
     *
     */
    public function test_distribution_key()
    {
        $this->assertEquals('id', $this->connections->getConnection('sharding')->getDistributionKey());
    }

    /**
     *
     */
    public function test_shard_connection_wrapper()
    {
        $sharding = $this->connections->getConnection('sharding');

        foreach ($sharding->getShardConnection() as $shard) {
            $this->assertEquals('Bdf\Prime\Connection\SimpleConnection', get_class($shard));
        }
    }

    /**
     *
     */
    public function test_wrapped_connection_of_shard()
    {
        $sharding = $this->connections->getConnection('sharding');
        $sharding->useShard('shard1');

        $this->assertEquals($sharding->getConnection('shard1')->getWrappedConnection(), $sharding->getWrappedConnection());
    }

    /**
     *
     */
    public function test_unknown_sub_connection()
    {
        $this->expectException(ShardingException::class);

        $this->connections->getConnection('sharding')->getConnection('unknown');
    }

    /**
     *
     */
    public function test_sub_connection_interface()
    {
        $master = $this->connections->getConnection('sharding');

        $this->assertEquals($master->getShardConnection('shard1'), $master->getConnection('shard1'));
        $this->assertEquals($master->getShardConnection('shard2'), $master->getConnection('shard2'));
    }

    /**
     *
     */
    public function test_connection_manager_access()
    {
        $shard = $this->connections->getConnection('sharding.shard1');

        $this->assertEquals('sharding.shard1', $shard->getName());
    }

    /**
     *
     */
    public function test_set_name()
    {

        $sharding = $this->connections->getConnection('sharding');
        $sharding->setName('sharding');

        $i = 1;
        foreach ($sharding->getShardConnection() as $shard) {
            $this->assertEquals('sharding.shard'.$i, $shard->getName());
            $i++;
        }

        $this->assertEquals('sharding', $sharding->getName());
    }

    /**
     *
     */
    public function test_get_shard_ids()
    {
        $this->assertEquals(['shard1', 'shard2'], $this->connections->getConnection('sharding')->getShardIds());
    }

    /**
     *
     */
    public function test_get_choser()
    {
        $this->assertEquals('Bdf\Prime\Sharding\ModuloChoser', get_class($this->connections->getConnection('sharding')->getShardChoser()));
    }

    /**
     *
     */
    public function test_pick_shard()
    {
        /** @var ShardingConnection $connection */
        $connection = $this->connections->getConnection('sharding');

        $connection->pickShard(0);
        $this->assertEquals('shard1', $connection->getCurrentShardId());

        $connection->pickShard(1);
        $this->assertEquals('shard2', $connection->getCurrentShardId());

        $connection->pickShard(2);
        $this->assertEquals('shard1', $connection->getCurrentShardId());
    }

    /**
     *
     */
    public function test_use_shard()
    {
        /** @var ShardingConnection $connection */
        $connection = $this->connections->getConnection('sharding');

        $connection->useShard('shard1');
        $this->assertEquals('shard1', $connection->getCurrentShardId());

        $connection->useShard('shard2');
        $this->assertEquals('shard2', $connection->getCurrentShardId());
    }

    /**
     *
     */
    public function test_close_reset_current_shard()
    {
        /** @var ShardingConnection $connection */
        $connection = $this->connections->getConnection('sharding');
        $connection->useShard('shard2');
        $connection->close();
        $this->assertEquals(null, $connection->getCurrentShardId());
    }

    /**
     * @group sharding
     */
    public function test_sharding()
    {
        /** @var ShardingConnection $connection */
        $connection = $this->connections->getConnection('sharding');

        //testing data
        $connection->insert('test', [
            'id'   => 10,
            'name' => 'shard1',
        ]);
        $connection->insert('test', [
            'id'   => 11,
            'name' => 'shard2',
        ]);

        $connection->pickShard(10);
        $rows = $connection->executeQuery('select * from test')->fetchAllAssociative();
        $this->assertEquals(1, count($rows));
        $this->assertEquals('shard1', $rows[0]['name']);

        $connection->pickShard(11);
        $rows = $connection->executeQuery('select * from test')->fetchAllAssociative();
        $this->assertEquals(1, count($rows));
        $this->assertEquals('shard2', $rows[0]['name']);

        $connection->useShard();

        $rows = $connection->executeQuery('select * from test')->fetchAllAssociative();
        $this->assertEquals(2, count($rows));
        $this->assertEquals('shard1', $rows[0]['name']);
        $this->assertEquals('shard2', $rows[1]['name']);

        $result = $connection->exec('delete from test');
        $this->assertEquals(2, $result);
        $this->assertEquals(0, count($connection->executeQuery('select * from test')->fetchAllAssociative()));
    }

    /**
     *
     */
    public function test_write_methods()
    {
        /** @var ShardingConnection $connection */
        $connection = $this->connections->getConnection('sharding');

        //testing data
        $connection->insert('test', [
            'id'   => 10,
            'name' => 'shard1',
        ]);
        $connection->insert('test', [
            'id'   => 11,
            'name' => 'shard2',
        ]);

        $connection->useShard();

        $rows = $connection->executeQuery('select * from test')->fetchAllAssociative();
        $this->assertEquals(2, count($rows));
        $this->assertEquals('shard1', $rows[0]['name']);
        $this->assertEquals('shard2', $rows[1]['name']);

        $result = $connection->executeUpdate('delete from test');
        $this->assertEquals(2, $result);
        $this->assertEquals(0, count($connection->executeQuery('select * from test')->fetchAllAssociative()));
    }

    /**
     *
     */
    public function test_count()
    {
        /** @var ShardingConnection $connection */
        $connection = $this->connections->getConnection('sharding');

        //testing data
        $connection->insert('test', [
            'id'   => 10,
            'name' => 'shard1',
        ]);
        $connection->insert('test', [
            'id'   => 11,
            'name' => 'shard2',
        ]);

        $connection->useShard();

        $count = $connection->executeQuery('select count(*) from test')->fetchFirstColumn();
        $this->assertEquals(1, $count[0]);
        $this->assertEquals(1, $count[1]);
        $this->assertEquals(2, $connection->from('test')->count());
    }

    /**
     *
     */
    public function test_last_insert_id()
    {
        /** @var ShardingConnection $connection */
        $connection = $this->connections->getConnection('sharding');

        //testing data
        $connection->pickShard(10);
        $connection->insert('test', [
            'id'   => 10,
            'name' => 'shard1',
        ]);

        $this->assertEquals(10, $connection->lastInsertId());
    }

    /**
     *
     */
    public function test_committed_transaction()
    {
        /** @var ShardingConnection $connection */
        $connection = $this->connections->getConnection('sharding');

        $connection->transactional(function($conn) {
            $conn->insert('test', [
                'id'   => 10,
                'name' => 'shard1',
            ]);
        });

        $this->assertEquals(1, $connection->from('test')->count());
        $this->assertEquals(1, count($connection->executeQuery('select * from test')->fetchAllAssociative()));
    }

    /**
     *
     */
    public function test_rollbacked_transaction()
    {
        /** @var ShardingConnection $connection */
        $connection = $this->connections->getConnection('sharding');

        try {
            $connection->transactional(function($conn) {
                $conn->insert('test', [
                    'id'   => 10,
                    'name' => 'shard1',
                ]);
                throw new \Exception('test');
            });
        } catch (\Exception $e) {

        }

        $this->assertEquals(0, $connection->from('test')->count());
        $this->assertEquals(0, count($connection->executeQuery('select * from test')->fetchAllAssociative()));
    }

    /**
     *
     */
    public function test_functionnal()
    {
        /** @var ShardingConnection $connection */
        $connection = $this->connections->getConnection('sharding');

        //testing data
        $connection->insert('test', [
            'id'   => 10,
            'name' => 'shard1',
        ]);
        $connection->insert('test', [
            'id'   => 11,
            'name' => 'shard2',
        ]);

        $this->assertEquals(2, count($connection->from('test')->all()));

        $rows = $connection->from('test')->where('id', 10)->first();
        $this->assertEquals('shard1', $rows['name']);

        $rows = $connection->from('test')->where('id', 9)->first();
        $this->assertEquals(null, $rows);

        $updated = $connection->from('test')->update(['name' => 'shard']);
        $this->assertEquals(2, $updated);
        $rows = $connection->from('test')->where('id', 10)->first();
        $this->assertEquals('shard', $rows['name']);

        $updated = $connection->from('test')->where('id', 10)->update(['name' => 'shard1']);
        $this->assertEquals(1, $updated);
        $rows = $connection->from('test')->where('id', 10)->first();
        $this->assertEquals('shard1', $rows['name']);

        $updated = $connection->from('test')->where('id', 10)->delete();
        $this->assertEquals(1, $updated);
        $rows = $connection->from('test')->where('id', 10)->first();
        $this->assertEquals(null, $rows);

        $updated = $connection->from('test')->delete();
        $this->assertEquals(1, $updated);
        $rows = $connection->from('test')->where('id', 10)->first();
        $this->assertEquals(0, count($connection->from('test')->all()));
    }

    /**
     *
     */
    public function test_make()
    {
        /** @var ShardingConnection $connection */
        $connection = $this->connections->getConnection('sharding');

        $this->assertInstanceOf(ShardingInsertQuery::class, $connection->make(InsertQueryInterface::class));
        $this->assertInstanceOf(ShardingKeyValueQuery::class, $connection->make(KeyValueQueryInterface::class));
    }
}
