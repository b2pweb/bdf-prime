<?php

namespace Bdf\Prime\Connection;

use Bdf\Prime\Connection\Event\ConnectionClosedListenerInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Connection\Result\UpdateResultSet;
use Bdf\Prime\Platform\Sql\Types\SqlBooleanType;
use Bdf\Prime\Platform\Sql\Types\SqlIntegerType;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Compiler\SqlCompiler;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use Bdf\Prime\Query\Custom\BulkInsert\BulkInsertQuery;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueSqlCompiler;
use Bdf\Prime\Query\Query;
use PHPUnit\Framework\TestCase;

// Declare read timeout in case the extension musqli does not support this constant.
// Doctrine will fail if this constant does not exist.
if (!defined('MYSQLI_OPT_READ_TIMEOUT')) {
    define('MYSQLI_OPT_READ_TIMEOUT', 11);
}

/**
 *
 */
class SimpleConnectionTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var SimpleConnection
     */
    protected $connection;
    
    /**
     * 
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->connection = Prime::connection('test');
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([
            'Bdf\Prime\TestEntity',
        ]);
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
    public function test_set_get_name()
    {
        $this->connection->setName('test');
        $this->assertEquals('test', $this->connection->getName());
    }
    
    /**
     * 
     */
    public function test_get_schema_manager()
    {
        $this->assertInstanceOf('Bdf\Prime\Schema\SchemaManager', $this->connection->schema());
    }
    
    /**
     * 
     */
    public function test_query_builder()
    {
        $this->assertInstanceOf('Bdf\Prime\Query\Query', $this->connection->builder());
        $this->assertInstanceOf(SqlCompiler::class, $this->connection->builder()->compiler());
    }

    /**
     *
     */
    public function test_make()
    {
        $this->assertInstanceOf(KeyValueQuery::class, $this->connection->make(KeyValueQuery::class));
        $this->assertInstanceOf(KeyValueSqlCompiler::class, $this->connection->make(KeyValueQuery::class)->compiler());

        $this->assertInstanceOf(Query::class, $this->connection->make(Query::class));
        $this->assertInstanceOf(SqlCompiler::class, $this->connection->make(Query::class)->compiler());
    }

    /**
     *
     */
    public function test_factory()
    {
        $this->assertInstanceOf(KeyValueSqlCompiler::class, $this->connection->factory()->compiler(KeyValueQuery::class));
        $this->assertInstanceOf(SqlCompiler::class, $this->connection->factory()->compiler(Query::class));

        $this->assertInstanceOf(KeyValueQuery::class, $this->connection->factory()->make(KeyValueQueryInterface::class));
        $this->assertInstanceOf(BulkInsertQuery::class, $this->connection->factory()->make(InsertQueryInterface::class));
    }
    
    /**
     * 
     */
    public function test_insert()
    {
        $now = new \DateTime();
        
        $this->connection->insert('test_', [
            'name'        => 'test',
            'date_insert' => $now,
        ]);
        
        $row = $this->connection->from('test_')->first();
        
        $this->assertEquals('test', $row['name']);
        $this->assertEquals($now->format('Y-m-d H:i:s'), $row['date_insert']);
    }
    
    /**
     * 
     */
    public function test_update()
    {
        $now = new \DateTime();
        
        $this->connection->insert('test_', ['name' => 'test']);
        
        $row = $this->connection->from('test_')->first();
        
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('test', $row['name']);
        $this->assertEquals(null, $row['date_insert']);
        
        $this->connection->update('test_', ['date_insert' => $now], ['id' => 1]);
        
        $row = $this->connection->from('test_')->first();
        
        $this->assertEquals('test', $row['name']);
        $this->assertEquals($now->format('Y-m-d H:i:s'), $row['date_insert']);
    }
    
    /**
     * 
     */
    public function test_delete()
    {
        $now = new \DateTime();
        
        $this->connection->insert('test_', [
            'name'        => 'test',
            'date_insert' => $now,
        ]);
        
        $this->connection->delete('test_', [
            'date_insert' => $now,
        ]);
        
        $row = $this->connection->from('test_')->first();
        
        $this->assertNull($row);
    }
    
    /**
     * 
     */
    public function test_query()
    {
        $this->connection->insert('test_', [
            'name' => 'test',
        ]);
        
        $result = $this->connection->query('select * from test_')->fetchAll();

        $this->assertEquals('test', $result[0]['name']);
    }
    
    /**
     * 
     */
    public function test_exec()
    {
        $this->connection->insert('test_', [
            'name' => 'test',
        ]);
        
        $result = $this->connection->exec('update test_ set name = "tested"');

        $this->assertEquals(1, $result);
    }
    
    /**
     * 
     */
    public function test_select()
    {
        $this->connection->insert('test_', [
            'id'   => 10,
            'name' => 'test',
        ]);
        
        $result = $this->connection->select('select * from test_ where id = :id', ['id' => 10]);
        $this->assertEquals('test', $result[0]->name);
        
        $result = $this->connection->select('select * from test_ where id = ?', [10]);
        $this->assertEquals('test', $result[0]->name);
    }

    /**
     *
     */
    public function test_fromDatabase_with_type_string()
    {
        $value = $this->connection->fromDatabase('1', 'boolean');

        $this->assertTrue($value);
    }

    /**
     *
     */
    public function test_fromDatabase_with_type_object()
    {
        $type = new SqlIntegerType($this->connection->platform());
        $value = $this->connection->fromDatabase('1', $type);

        $this->assertSame(1, $value);
    }

    /**
     *
     */
    public function test_fromDatabase_with_facade_type()
    {
        $value = $this->connection->fromDatabase('{"foo":"bar"}', 'json');

        $this->assertSame(['foo' => 'bar'], $value);
    }

    /**
     *
     */
    public function test_toDatabase_with_type_string()
    {
        $value = $this->connection->toDatabase(true, 'boolean');

        $this->assertSame(1, $value);
    }

    /**
     *
     */
    public function test_toDatabase_with_type_object()
    {
        $type = new SqlBooleanType($this->connection->platform());
        $value = $this->connection->toDatabase(true, $type);

        $this->assertSame(1, $value);
    }

    /**
     *
     */
    public function test_toDatabase_without_type()
    {
        $value = $this->connection->toDatabase(true);

        $this->assertSame(1, $value);
    }

    /**
     *
     */
    public function test_toDatabase_with_facade_type()
    {
        $value = $this->connection->toDatabase(['foo' => 'bar'], 'json');

        $this->assertSame('{"foo":"bar"}', $value);
    }

    /**
     *
     */
    public function test_execute_select_query()
    {
        $query = $this->connection->builder()->from('test_');

        $this->connection->insert('test_', [
            'id'   => 10,
            'name' => 'test',
        ]);

        $this->assertEquals([[
            'id'          => 10,
            'name'        => 'test',
            'foreign_key' => null,
            'date_insert' => null,
        ]], $this->connection->execute($query)->all());
    }

    /**
     *
     */
    public function test_execute_update_query()
    {
        $this->connection->insert('test_', [
            'id'   => 10,
            'name' => 'test',
        ]);

        $query = new class implements Compilable {
            public function compile($forceRecompile = false)
            {
                return 'UPDATE test_ SET name = ? WHERE id = ?';
            }

            public function getBindings()
            {
                return ['new-name', 10];
            }

            public function type()
            {
                return self::TYPE_UPDATE;
            }
        };

        $result = $this->connection->execute($query);
        $this->assertSame(1, $result->count());
        $this->assertInstanceOf(UpdateResultSet::class, $result);
        $this->assertSame($result, $result->fetchMode(ResultSetInterface::FETCH_OBJECT));
        $this->assertSame([], $result->all());
        $this->assertSame([], iterator_to_array($result));

        $this->assertEquals('new-name', $this->connection->builder()->from('test_')->where('id', 10)->inRow('name'));
    }

    /**
     *
     */
    public function test_execute_select_prepared()
    {
        $this->connection->insert('test_', [
            'id'   => 10,
            'name' => 'test',
        ]);

        $query = $this->createMock(Compilable::class);

        //$query->expects($this->once())->method('type')->willReturn(Compilable::TYPE_SELECT);
        $query->expects($this->once())->method('compile')->willReturn($this->connection->prepare('SELECT * FROM test_ WHERE id = ?'));
        $query->expects($this->once())->method('getBindings')->willReturn([10]);

        $this->assertEquals([[
            'id'          => 10,
            'name'        => 'test',
            'foreign_key' => null,
            'date_insert' => null,
        ]], $this->connection->execute($query)->all());
    }

    /**
     *
     */
    public function test_execute_update_prepared()
    {
        $this->connection->insert('test_', [
            'id'   => 10,
            'name' => 'test',
        ]);

        $query = $this->createMock(Compilable::class);

        //$query->expects($this->once())->method('type')->willReturn(Compilable::TYPE_DELETE);
        $query->expects($this->once())->method('compile')->willReturn($this->connection->prepare('DELETE FROM test_ WHERE id = ?'));
        $query->expects($this->once())->method('getBindings')->willReturn([10]);

        $this->assertCount(1, $this->connection->execute($query));
        $this->assertEmpty($this->connection->builder()->from('test_')->all());
    }

    /**
     * PHP Bug #78192 https://bugs.php.net/bug.php?id=78192
     */
    public function test_execute_select_prepared_with_schemas_changed()
    {
        if (PHP_VERSION_ID >= 70200) {
            return;
        }

        $this->connection->insert('test_', [
            'id'   => 10,
            'name' => 'test',
        ]);

        $query = new class($this->connection) implements Compilable {
            private $connection;
            private $statement;

            public function __construct($connection)
            {
                $this->connection = $connection;
            }

            public function compile($forceRecompile = false)
            {
                if (!$this->statement || $forceRecompile) {
                    $this->statement = $this->connection->prepare('SELECT * FROM test_ WHERE id = ?');
                }

                return $this->statement;
            }

            public function getBindings()
            {
                return [10];
            }

            public function type()
            {
                return self::TYPE_SELECT;
            }
        };

        $this->assertEquals([[
            'id'          => 10,
            'name'        => 'test',
            'foreign_key' => null,
            'date_insert' => null
        ]], $this->connection->execute($query)->all());

        $this->connection->exec('ALTER TABLE test_ ADD new_col VARCHAR(255)');

        $this->assertEquals([[
            'id'          => 10,
            'name'        => 'test',
            'foreign_key' => null,
            'date_insert' => null,
            'new_col'     => null
        ]], $this->connection->execute($query)->all());
    }

    /**
     *
     */
    public function test_close_should_call_listener()
    {
        $listener = $this->createMock(ConnectionClosedListenerInterface::class);
        $this->connection->getEventManager()->addEventListener(ConnectionClosedListenerInterface::EVENT_NAME, $listener);

        $listener->expects($this->once())->method('onConnectionClosed');

        $this->connection->close();
    }

    /**
     * @group prime-reconnection
     */
    public function test_reconnection()
    {
        Prime::service()->connections()->declareConnection('test_reconnection', [
            'driver' => 'mysqli',
            'user' => 'root',
            'host' => '127.0.0.1',
//            'adapter' => 'sqlite',
//            'memory' => true
        ]);
        $connection = Prime::service()->connections()->getConnection('test_reconnection');
        Prime::service()->connections()->removeConnection('test_reconnection');
        $connection->exec('SET SESSION wait_timeout=1');

        $connection->select('select 1');
        sleep(2);
        $result = $connection->select('select 1 as dummy');

        $this->assertEquals(1, $result[0]->dummy);
    }

    /**
     * @group prime-reconnection
     */
    public function test_reconnection_on_prepared_query()
    {
        Prime::service()->connections()->declareConnection('test_reconnection', [
            'driver' => 'mysqli',
            'user' => 'root',
            'host' => '127.0.0.1',
//            'adapter' => 'sqlite',
//            'memory' => true
        ]);
        $connection = Prime::service()->connections()->getConnection('test_reconnection');
        Prime::service()->connections()->removeConnection('test_reconnection');
        $connection->exec('SET SESSION wait_timeout=1');

        $query = new class($connection) implements Compilable {
            private $connection;
            private $statement;

            public function __construct($connection)
            {
                $this->connection = $connection;
            }

            public function compile($forceRecompile = false)
            {
                if (!$this->statement || $forceRecompile) {
                    $this->statement = $this->connection->prepare('SELECT 1 as dummy');
                }

                return $this->statement;
            }

            public function getBindings()
            {
                return [];
            }

            public function type()
            {
                return self::TYPE_SELECT;
            }
        };

        sleep(2); // timeout for the prepare
        $connection->execute($query)->all();
        sleep(2); // timeout for the execute query
        $result = $connection->execute($query)->all();

        $this->assertEquals(1, $result[0]['dummy']);
    }

    /**
     *
     */
    public function test_should_not_connect_if_no_query_is_executed()
    {
        // Use MySQL because Doctrine resolve the server version when getting the platform
        $this->prime()->connections()->declareConnection('other_connection', ['adapter' => 'mysql']);

        $connection = $this->prime()->connection('other_connection');

        $this->assertFalse($connection->isConnected());
    }
}
