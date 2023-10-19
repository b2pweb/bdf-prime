<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Customer;
use Bdf\Prime\CustomerCriteria;
use Bdf\Prime\Document;
use Bdf\Prime\DoubleJoinEntityMaster;
use Bdf\Prime\EntityWithCallableKey;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Attribute;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\Query\Expression\Operator;
use Bdf\Prime\Query\Expression\Raw;
use Bdf\Prime\Query\Expression\RawValue;
use Bdf\Prime\Query\Expression\Value;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\Right;
use Bdf\Prime\TestEntity;
use Bdf\Prime\TestFiltersEntity;
use Bdf\Prime\TestFiltersEntityMapper;
use Bdf\Prime\User;
use Doctrine\DBAL\Cache\ArrayResult;
use Doctrine\DBAL\Result;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class QueryOrmTest extends TestCase
{
    use PrimeTestCase;

    protected $repository;
    protected $query;
    protected $table;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->primeStart();
        
        $this->repository = Prime::repository('Bdf\Prime\TestEntity');
        $this->table = $this->repository->mapper()->metadata()->table();
        $this->query = $this->repository->builder();
        
        $connection = $this->createConnectionMock();
        $connection->expects($this->any())->method('executeQuery')->willReturn(new Result(new ArrayResult([]), $connection));
        
        $this->query->on($connection);
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
    public function test_insert()
    {
        $data = ['id' => 1, 'name' => 'Test name', 'foreign.id' => 'foreign'];
        $this->query->insert($data);
            
        $this->assertEquals("INSERT INTO $this->table (id, name, foreign_key) VALUES(?, ?, ?)", 
            $this->query->toSql());
        
        $this->assertEquals([1, 'Test name', 'foreign'], $this->query->getBindings());
    }

    /**
     *
     */
    public function test_insert_with_undeclared_field()
    {
        $data = ['id' => 1, 'name' => 'Test name', 'undefined' => 'my value'];
        $this->query->insert($data);

        $this->assertEquals("INSERT INTO $this->table (id, name, undefined) VALUES(?, ?, ?)",
            $this->query->toSql());

        $this->assertEquals([1, 'Test name', 'my value'], $this->query->getBindings());
    }
    
    /**
     * 
     */
    public function test_insert_ignore()
    {
        $data = ['id' => 1, 'name' => 'Test name', 'foreign.id' => 'foreign'];
        $this->query->ignore()->insert($data);
        
        $this->assertStringContainsString('IGNORE', $this->query->toSql());
        
        if ($this->repository->connection()->platform()->name() === 'mysql') {
            $this->assertEquals("INSERT IGNORE INTO $this->table (id, name, foreign_key) VALUES(?, ?, ?)", $this->query->toSql());
        } else {
            $this->assertEquals("INSERT OR IGNORE INTO $this->table (id, name, foreign_key) VALUES(?, ?, ?)", $this->query->toSql());
        }
    }
    
    /**
     * 
     */
    public function test_update()
    {
        $data = ['id' => 1, 'name' => 'Test name', 'foreign.id' => 'foreign'];
        $this->query->update($data);
        
        $this->assertEquals("UPDATE $this->table SET id = ?, name = ?, foreign_key = ?", 
            $this->query->toSql());
        
        $this->assertEquals([1, 'Test name', 'foreign'], $this->query->getBindings());
    }
    
    /**
     * 
     */
    public function test_replace()
    {
        $data = ['id' => 1, 'name' => 'Test name', 'foreign.id' => 'foreign'];
        $this->query->replace($data);
        
        $this->assertEquals("REPLACE INTO $this->table (id, name, foreign_key) VALUES(?, ?, ?)", 
            $this->query->toSql());
        
        $this->assertEquals([1, 'Test name', 'foreign'], $this->query->getBindings());
    }
    
    /**
     * 
     */
    public function test_delete()
    {
        $this->query->delete();
        
        $this->assertEquals("DELETE FROM $this->table", 
            $this->query->toSql());
    }
    
    /**
     * 
     */
    public function test_simple_select()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0", 
            $this->query->toSql());
    }
    
    /**
     * 
     */
    public function test_distinct()
    {
        $this->assertEquals("SELECT DISTINCT t0.id FROM $this->table t0", 
            $this->query->select('id')->distinct()->toSql());
    }
    
    /**
     * 
     */
    public function test_limit()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 LIMIT 1", 
            $this->query->limit(1)->toSql());
        
        $this->assertEquals(1, $this->query->getLimit());
        $this->assertEquals(0, $this->query->getOffset());
    }
    
    /**
     * 
     */
    public function test_limit_with_offset()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 LIMIT 1 OFFSET 10", 
            $this->query->limit(1, 10)->toSql());
        
        $this->assertEquals(1, $this->query->getLimit());
        $this->assertEquals(10, $this->query->getOffset());
    }
    
    /**
     * 
     */
    public function test_limit_with_offset_method()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 LIMIT 1 OFFSET 10", 
            $this->query->limit(1)->offset(10)->toSql());
        
        $this->assertEquals(1, $this->query->getLimit());
        $this->assertEquals(10, $this->query->getOffset());
    }
    
    /**
     *
     */
    public function test_limit_page()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 LIMIT 1",
            $this->query->limitPage(1)->toSql());

        $this->assertEquals(1, $this->query->getLimit());
        $this->assertEquals(0, $this->query->getOffset());
    }

    /**
     * 
     */
    public function test_limit_page_with_offset()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 LIMIT 10 OFFSET 10", 
            $this->query->limitPage(2, 10)->toSql());
        
        $this->assertEquals(10, $this->query->getLimit());
        $this->assertEquals(10, $this->query->getOffset());
    }
    
    /**
     * 
     */
    public function test_isLimitQuery()
    {
        $this->assertFalse($this->repository->builder()->isLimitQuery());
        $this->assertTrue($this->repository->builder()->limit(1)->isLimitQuery());
        $this->assertTrue($this->repository->builder()->offset(1)->isLimitQuery());
        $this->assertTrue($this->repository->builder()->limitPage(1)->isLimitQuery());
    }
    
    /**
     * 
     */
    public function test_order()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 ORDER BY t0.name ASC, t0.foreign_key DESC, t0.id ASC", 
            $this->query
            ->order([
                'name',
                'foreign.id' => 'DESC'
            ])
            ->addOrder('id')
            ->toSql());
    }
    
    /**
     * 
     */
    public function test_empty_get_orders()
    {
        $this->assertEquals([], $this->query->getOrders());
    }
    
    /**
     * 
     */
    public function test_get_orders()
    {
        $this->query->order('id');
        
        $this->assertEquals(['id' => 'ASC'], $this->query->getOrders());
    }
    
    /**
     * 
     */
    public function test_get_orders_with_alias()
    {
        $this->query->order('dateInsert', 'DESC');
        
        $this->assertEquals(['dateInsert' => 'DESC'], $this->query->getOrders());
    }
    
    /**
     * 
     */
    public function test_group()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 GROUP BY t0.name, t0.foreign_key", 
            $this->query
            ->group('name', 'foreign.id')
            ->toSql());
    }
    
    /**
     * 
     */
    public function test_having()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 HAVING t0.name LIKE ? AND t0.foreign_key = ?", 
            $this->query
            ->having([
                'name :like' => 'Test%',
                'foreign.id' => 2,
            ])
            ->toSql());
        
        $this->assertEquals(['Test%', 2], $this->query->getBindings());
    }
    
    /**
     * 
     */
    public function test_basic_where()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 WHERE t0.name LIKE ? AND t0.foreign_key = ?", 
            $this->query
            ->where([
                'name :like' => 'Test%',
                'foreign.id' => 2,
            ])
            ->toSql());
        
        $this->assertEquals(['Test%', 2], $this->query->getBindings());
    }
    
    /**
     * 
     */
    public function test_where_null()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 WHERE t0.name IS NULL", 
            $this->query
            ->where('name', null)
            ->toSql());
    }

    /**
     *
     */
    public function test_where_null_by_array()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 WHERE t0.name IS NULL",
            $this->query
            ->where([
                'name' => null,
            ])
            ->toSql());
    }

    /**
     * 
     */
    public function test_2_wheres()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 WHERE (t0.name IS NULL) AND (t0.id = ?)", 
            $this->query
            ->where([
                'name' => null,
            ])
            ->where([
                'id' => 1,
            ])
            ->toSql());
    }
    
    /**
     * 
     */
    public function test_where_can_simulate_into_clause()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 WHERE (t0.name = ?) AND (t0.id = ? OR t0.id != ?)", 
            $this->query
            ->where([
                'name' => 'Test',
            ])
            ->where([
                'id ='  => 1,
                'id !=' => -1,
            ], 'OR')
            ->toSql());
        
        $this->assertEquals(['Test', 1, -1], $this->query->getBindings());
    }
    
    /**
     * 
     */
    public function test_where_on_complex_attribute()
    {
        $now = new \DateTime();
        
        $this->assertEquals("SELECT t0.* FROM $this->table t0 WHERE t0.date_insert > ?", 
            $this->query
            ->where([
                'dateInsert >' => $now,
            ])
            ->toSql());
        
        $this->assertEquals([$now->format('Y-m-d H:i:s')], $this->query->getBindings());
    }
    
    /**
     * 
     */
    public function test_where_in_on_complex_attribute()
    {
        $now = new \DateTime('2017-05-03 12:20:12');
        
        $this->assertEquals("SELECT t0.* FROM $this->table t0 WHERE t0.date_insert IN (?)", 
            $this->query
            ->where([
                'dateInsert :in' => [$now],
            ])
            ->toSql());
        
        $this->assertEquals(['2017-05-03 12:20:12'], $this->query->getBindings());
    }
    
    /**
     * 
     */
    public function test_where_scalar_value_on_complex_attribute()
    {
        $date = '2015-08-10';
        
        $this->assertEquals("SELECT t0.* FROM $this->table t0 WHERE t0.date_insert > ?", 
            $this->query
            ->where([
                'dateInsert >' => $date,
            ])
            ->toSql());
        
        $this->assertEquals([$date], $this->query->getBindings());
    }
    
    /**
     * 
     */
    public function test_orwhere_into()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 WHERE (t0.name = ?) OR (t0.id = ? AND t0.id != ?)", 
            $this->query
            ->orWhere([
                'name' => 'Test',
            ])
            ->orWhere([
                'id ='  => 1,
                'id !=' => -1,
            ], 'AND')
            ->toSql());
        
        $this->assertEquals(['Test', 1, -1], $this->query->getBindings());
    }
    
    /**
     * 
     */
    public function test_where_raw()
    {
        // The compiler has no access to raw value
        $this->assertEquals("SELECT t0.* FROM $this->table t0 WHERE id BETWEEN ...",
            $this->query
            ->whereRaw('id BETWEEN ...')
            ->toSql());
    }

    /**
     *
     */
    public function test_or_where_raw()
    {
        // The compiler has no access to raw value
        $this->assertEquals("SELECT t0.* FROM $this->table t0 WHERE id = 1 OR id BETWEEN ...",
            $this->query
            ->orWhereRaw('id = 1')
            ->orWhereRaw('id BETWEEN ...')
            ->toSql());
    }

    /**
     * 
     */
    public function test_where_with_subQuery()
    {
        $subQuery = $this->repository->builder()->select('id');
        
        $this->assertEquals("SELECT t0.* FROM $this->table t0 WHERE t0.id IN (SELECT t0.id FROM $this->table t0)",
            $this->query
            ->where(['id :in' => $subQuery])
            ->toSql());
    }
    
    /**
     * 
     */
    public function test_select_with_subQuery()
    {
        $subQuery = $this->repository->builder()->select('id');
        
        $this->assertEquals("SELECT (SELECT t0.id FROM $this->table t0) as q FROM $this->table t0",
            $this->query->select(['q' => $subQuery])
            ->toSql());
    }
    
    /**
     * 
     */
    public function test_not_in_command()
    {
        $this->assertEquals("SELECT t0.* FROM $this->table t0 WHERE t0.id NOT IN (?)",
            $this->query
            ->where(['id :notin' => [1]])
            ->toSql());
    }
    
    /**
     * 
     */
    public function test_auto_join()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0 INNER JOIN foreign_ t1 ON t1.pk_id = t0.foreign_key WHERE t0.name = ? AND t0.foreign_key NOT IN (?) AND t1.name_ LIKE ? AND t1.city LIKE ?",
            
            $this->query
            ->where([
                'name' => 'entity',
                'foreign.id :notin' => [1],
                'foreign.name :like' => 'test%',
                'foreign.city :like' => 'test%',
            ])
            ->toSql()
        );
    }
    
    /**
     * 
     */
    public function test_auto_join_and_join()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0 INNER JOIN foreign_ foreign ON foreign.pk_id = t0.foreign_key WHERE t0.name = ? AND foreign.name_ LIKE ? AND foreign.city LIKE ?",
            
            $this->query
            ->joinEntity('Bdf\Prime\TestEmbeddedEntity', 'id', 'foreign.id', 'foreign')
            ->where([
                'name' => 'entity',
                'foreign.name :like' => 'test%',
                'foreign.city :like' => 'test%',
            ])
            ->toSql()
        );
    }

    /**
     *
     */
    public function test_entityJoin_with_callable_string_fk_should_be_used_as_string()
    {
        $this->assertEquals(
            "SELECT t0.* FROM entity_with_callable_key t0 INNER JOIN entity_with_callable_key alias ON alias.key = t0.key",
            EntityWithCallableKey::builder()->joinEntity(EntityWithCallableKey::class, 'key', 'key', 'alias')->toSql()
        );
    }

    /**
     * 
     */
    public function test_where_expression_without_criterion()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0 WHERE 1 AND 2",
            
            $this->query
            ->where([new Raw('1'), new Raw('2')])
            ->toSql()
        );
    }
    
    /**
     *
     */
    public function test_where_into()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0 WHERE t0.name IS NOT NULL AND (t0.name LIKE ? OR t0.name LIKE ?)",
            
            $this->query
            ->where([
                'name !=' => null,
                'name :like' => ['test1%', 'test2%'],
            ])
            ->toSql()
        );
    }

    /**
     *
     */
    public function test_from_entity_class()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0, customer_ c WHERE c.name_ = ?",

            $this->query->from(Customer::class, 'c')->where('c.name', 'test')->toSql()
        );
    }

    /**
     *
     */
    public function test_from_entity_class_with_alias()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0, customer_ c WHERE c.name_ = ?",

            $this->query->from(Customer::class, 'c')->where('$c.name', 'test')->toSql()
        );
    }

    /**
     *
     */
    public function test_from_dbal_value()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0, customer_ c WHERE c.name_ = ?",

            $this->query->from('customer_', 'c')->where('c.name_', 'test')->toSql()
        );
    }

    /**
     *
     */
    public function test_from_dbal_value_disable_unknown_attributes()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown attribute "c.name_" on entity "Bdf\Prime\TestEntity"');

        $this->query->allowUnknownAttribute(false);

        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0, customer_ c WHERE c.name_ = ?",

            $this->query->from('customer_', 'c')->where('c.name_', 'test')->toSql()
        );
    }

    /**
     *
     */
    public function test_from_dbal_value_without_alias()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0, customer_ WHERE customer_.name_ = ?",

            $this->query->from('customer_')->where('customer_.name_', 'test')->toSql()
        );
    }

    /**
     *
     */
    public function test_count_pagination()
    {
        $connection = $this->createConnectionMock();
        $connection->expects($this->once())->method('executeQuery')
            ->with("SELECT COUNT(*) AS aggregate FROM $this->table t0 WHERE t0.name = ?")
            ->willReturn(new Result(new ArrayResult([['aggregate' => 1]]), $connection));

        $this->query->on($connection)->where('name', 'test')->paginationCount();
    }

    /**
     *
     */
    public function test_count_pagination_field()
    {
        $connection = $this->createConnectionMock();
        $connection->expects($this->once())->method('executeQuery')
            ->with("SELECT COUNT(*) AS aggregate FROM $this->table t0 WHERE t0.name = ?")
            ->willReturn(new Result(new ArrayResult([['aggregate' => 1]]), $connection));

        $this->query->on($connection)->select('name')->where('name', 'test')->paginationCount();
    }

    /**
     * @todo Non géré par la nouvelle archi
     */
//    public function test_count_pagination_distinct_primary()
//    {
//        $connection = $this->getMockBuilder(SimpleConnection::class)
//            ->disableOriginalConstructor()
//            ->setMethods(['executeUpdate', 'executeQuery', 'getDatabasePlatform'])
//            ->getMock();
//        $connection->expects($this->any())->method('getDatabasePlatform')->willReturn($this->repository->connection()->getDatabasePlatform());
//        $connection->expects($this->once())->method('executeQuery')
//            ->with("SELECT COUNT(DISTINCT IFNULL(t0.id,\"___null___\")) AS aggregate FROM $this->table t0 WHERE t0.name = ?")
//            ->willReturn(new CacheStatement([['aggregate' => 1]]));
//
//        $this->query->on($connection)->distinct()->where('name', 'test')->paginationCount();
//    }

    /**
     *
     */
    public function test_count_pagination_distinct_field()
    {
        $connection = $this->createConnectionMock();
        $connection->expects($this->once())->method('executeQuery')
            ->with("SELECT COUNT(DISTINCT IFNULL(t0.name,\"___null___\")) AS aggregate FROM $this->table t0 WHERE t0.name = ?")
            ->willReturn(new Result(new ArrayResult([['aggregate' => 1]]), $connection));

        $this->query->on($connection)->select('name')->distinct()->where('name', 'test')->paginationCount();
    }

    /**
     *
     */
    public function test_count_pagination_distinct_fields()
    {
        $connection = $this->createConnectionMock();
        $connection->expects($this->once())->method('executeQuery')
            ->with("SELECT COUNT(DISTINCT IFNULL(t0.name,\"___null___\")) AS aggregate FROM $this->table t0 WHERE t0.name = ?")
            ->willReturn(new Result(new ArrayResult([['aggregate' => 1]]), $connection));

        $this->query->on($connection)->distinct()->select('name', 'dateInsert')->where('name', 'test')->paginationCount();
    }

    /**
     *
     */
    public function test_count_pagination_group_by()
    {
        $connection = $this->createConnectionMock();
        $connection->expects($this->once())->method('executeQuery')
            ->with("SELECT COUNT(DISTINCT IFNULL(t0.name,\"___null___\")) AS aggregate FROM $this->table t0 WHERE t0.name = ?")
            ->willReturn(new Result(new ArrayResult([['aggregate' => 1]]), $connection));

        $this->query->on($connection)->group('name', 'dateInsert')->where('name', 'test')->paginationCount();
    }

    /**
     *
     */
    public function test_count()
    {
        $connection = $this->createConnectionMock();
        $connection->expects($this->once())->method('executeQuery')
            ->with("SELECT COUNT(*) AS aggregate FROM $this->table t0 WHERE t0.name = ?")
            ->willReturn(new Result(new ArrayResult([['aggregate' => 1]]), $connection));

        $this->query->on($connection)->select('name', 'dateInsert')->where('name', 'test')->count();
    }

    /**
     *
     */
    public function test_count_field()
    {
        $connection = $this->createConnectionMock();
        $connection->expects($this->once())->method('executeQuery')
            ->with("SELECT COUNT(t0.name) AS aggregate FROM $this->table t0 WHERE t0.name = ?")
            ->willReturn(new Result(new ArrayResult([['aggregate' => 1]]), $connection));

        $this->query->on($connection)->where('name', 'test')->count('name');
    }

    /**
     *
     */
    public function test_count_distinct()
    {
        $connection = $this->createConnectionMock();
        $connection->expects($this->once())->method('executeQuery')
            ->with("SELECT COUNT(DISTINCT t0.name) AS aggregate FROM $this->table t0 WHERE t0.name = ?")
            ->willReturn(new Result(new ArrayResult([['aggregate' => 1]]), $connection));

        $this->query->on($connection)->distinct()->where('name', 'test')->count('name');
    }
    
    /**
     *
     */
    public function test_count_join()
    {
        $connection = $this->createConnectionMock();
        $connection->expects($this->once())->method('executeQuery')
            ->with("SELECT COUNT(*) AS aggregate FROM $this->table t0 INNER JOIN foreign_ t1 ON t1.pk_id = t0.foreign_key WHERE t0.name = ? AND t1.name_ LIKE ? AND t1.city LIKE ?")
            ->willReturn(new Result(new ArrayResult([['aggregate' => 1]]), $connection));

        $this->query
            ->on($connection)
            ->where([
                'name' => 'entity',
                'foreign.name :like' => 'test%',
                'foreign.city :like' => 'test%',
            ])
            ->count();
    }

    /**
     * @dataProvider getAggregate
     */
    public function test_aggregate($method)
    {
        $connection = $this->createConnectionMock();
        $connection->expects($this->once())->method('executeQuery')
            ->with("SELECT ".strtoupper($method)."(*) AS aggregate FROM $this->table t0")
            ->willReturn(new Result(new ArrayResult([['aggregate' => 1]]), $connection));

        $this->query->on($connection)->$method();
    }

    /**
     * @dataProvider getAggregate
     */
    public function test_aggregate_field($method)
    {
        $connection = $this->createConnectionMock();
        $connection->expects($this->once())->method('executeQuery')
            ->with("SELECT ".strtoupper($method)."(DISTINCT t0.name) AS aggregate FROM $this->table t0")
            ->willReturn(new Result(new ArrayResult([['aggregate' => 1]]), $connection));

        $this->query->on($connection)->distinct()->$method('name');
    }

    public function getAggregate()
    {
        return [
            ['avg'],
            ['min'],
            ['max'],
            ['sum'],
        ];
    }

    /**
     *
     */
    public function test_unknown_aggregate()
    {
        $connection = $this->createConnectionMock();
        $connection->expects($this->once())->method('executeQuery')
            ->with("SELECT MD5(DISTINCT t0.name) AS aggregate FROM $this->table t0")
            ->willReturn(new Result(new ArrayResult([['aggregate' => 1]]), $connection));

        $this->query->on($connection)->distinct()->aggregate('md5', 'name');
    }

    /**
     *
     */
    public function test_quote_identifier()
    {
        $query = Right::where('id', '>', 1);

        $this->assertEquals('SELECT "t0".* FROM "rights_" "t0" WHERE "t0"."id_" > ?', $query->toSql());
    }

    /**
     *
     */
    public function test_double_join()
    {
        $repository = Prime::repository(DoubleJoinEntityMaster::class);

        $query = $repository->builder();

        $query->select('*')->where([
            'sub.name' => 'toto',
            'sub2.sub.name' => 'toto'
        ]);

        $this->assertEquals(
            'SELECT t0.* FROM double_join_entity_master t0 INNER JOIN double_join_entity_sub t1 ON t1.id = t0.sub_id INNER JOIN double_join_entity_sub2 t2 ON t2.id = t0.sub2_id INNER JOIN double_join_entity_sub t3 ON t3.id = t2.sub_id WHERE t1.name = ? AND t3.name = ?',
            $query->toSql()
        );
    }

    /**
     *
     */
    public function test_filters()
    {
        /** @var RepositoryInterface $repository */
        $repository = Prime::repository(TestFiltersEntity::class);

        /** @var TestFiltersEntityMapper $mapper */
        $mapper = $repository->mapper();

        $inQuery = null;
        $inValue = null;

        $mapper->filters = [
            'myFilter' => function ($query, $value) use (&$inQuery, &$inValue) {
                $inQuery = $query;
                $inValue = $value;
            }
        ];

        $query = $repository->builder();
        $query->where('myFilter', 45);

        $this->assertSame($query, $inQuery);
        $this->assertEquals(45, $inValue);
    }

    /**
     *
     */
    public function test_optimized_expression()
    {
        $query = Customer::where('"webUsers.faction">name', 'orc')->orWhere('"webUsers.faction">id', 5);

        $this->assertEquals(
            'SELECT t0.* FROM customer_ t0 INNER JOIN user_ t1 ON t1.customer_id = t0.id_ INNER JOIN faction_ t2 ON t2.id_ = t1.faction_id WHERE t2.name_ = ? OR t2.id_ = ? AND (t2.domain_ = ?) AND (t2.enabled_ = ?) AND (t2.domain_ = ?)',
            $query->toSql()
        );
    }

    /**
     * @see http://192.168.0.187:3000/issues/12086
     */
    public function test_where_in_complex_type()
    {
        $query = User::where('roles', ':in', [[1, 2], [1]]);

        $this->assertEquals('SELECT t0.* FROM user_ t0 WHERE t0.roles_ IN (?,?)', $query->toSql());

        $this->assertEquals([
            ',1,2,',
            ',1,',
        ], $query->getBindings());
    }

    /**
     *
     */
    public function test_where_transformer_value()
    {
        $query = User::where('roles', new Value([1, 3]));

        $this->assertEquals('SELECT t0.* FROM user_ t0 WHERE t0.roles_ = ?', $query->toSql());

        $this->assertEquals([',1,3,'], $query->getBindings());
    }

    /**
     *
     */
    public function test_where_rawValue()
    {
        $query = User::where('roles', new RawValue(',1,3,'));

        $this->assertEquals('SELECT t0.* FROM user_ t0 WHERE t0.roles_ = ?', $query->toSql());

        $this->assertEquals([',1,3,'], $query->getBindings());
    }

    /**
     *
     */
    public function test_where_like_value()
    {
        $query = User::where('roles', (new Like([1, 3]))->searchableArray());

        $this->assertEquals('SELECT t0.* FROM user_ t0 WHERE (t0.roles_ LIKE ? OR t0.roles_ LIKE ?)', $query->toSql());

        $this->assertEquals(['%,1,%', '%,3,%'], $query->getBindings());
    }

    /**
     *
     */
    public function test_where_in_transformer()
    {
        $query = User::where('roles', [['1'], ['1', '2']]);

        $this->assertEquals('SELECT t0.* FROM user_ t0 WHERE t0.roles_ IN (?,?)', $query->toSql());
        $this->assertEquals([
            ',1,',
            ',1,2,'
        ], $query->getBindings());
    }

    /**
     *
     */
    public function test_left_join()
    {
        $query = User::leftJoinEntity(Customer::class, 'id', 'customer.id', 'c');
        $query->order('c.name');

        $this->assertEquals('SELECT t0.* FROM user_ t0 LEFT JOIN customer_ c ON c.id_ = t0.customer_id ORDER BY c.name_ ASC', $query->toSql());
    }

    /**
     *
     */
    public function test_right_join()
    {
        $query = User::rightJoinEntity(Customer::class, 'id', 'customer.id', 'c');
        $query->order('c.name');

        $this->assertEquals('SELECT t0.* FROM user_ t0 RIGHT JOIN customer_ c ON c.id_ = t0.customer_id ORDER BY c.name_ ASC', $query->toSql());
    }

    /**
     *
     */
    public function test_inner_join()
    {
        $query = User::joinEntity(Customer::class, 'id', 'customer.id', 'c');
        $query->order('c.name');

        $this->assertEquals('SELECT t0.* FROM user_ t0 INNER JOIN customer_ c ON c.id_ = t0.customer_id ORDER BY c.name_ ASC', $query->toSql());
    }
    /**
     *
     */
    public function test_joinEntity_no_alias_will_throw_exception()
    {
        $this->expectException(\LogicException::class);

        $query = Document::builder();
        $query->joinEntity(Customer::class, 'customerId', 'id');
    }

    /**
     *
     */
    public function test_joinEntity()
    {
        $query = Document::builder();
        $query->joinEntity(Customer::class, 'customerId', 'id', 't1');

        $this->assertEquals([
            'type' => 'INNER',
            'table' => Customer::class,
            'alias' => 't1',
            'on'    => [
                [
                    'column' => 't1>customerId',
                    'operator' => '=',
                    'value' => new Attribute('id'),
                    'glue' => 'AND'
                ]
            ]
        ], end($query->statements['joins']));
    }

    /**
     *
     */
    public function test_joinEntity_with_closure()
    {
        $query = Document::builder();

        $query->joinEntity(Customer::class, function (JoinClause $join) {
            $join->on('customerId', '>', 'id');
        }, null, 't1');

        $this->assertEquals([
            'type' => 'INNER',
            'table' => Customer::class,
            'alias' => 't1',
            'on'    => [
                [
                    'column' => 'customerId',
                    'operator' => '>',
                    'value' => 'id',
                    'glue' => 'AND'
                ]
            ]
        ], end($query->statements['joins']));
    }

    /**
     *
     */
    public function test_rightJoinEntity()
    {
        $query = Document::builder();
        $query->rightJoinEntity(Customer::class, 'customerId', 'id', 't1');

        $this->assertEquals([
            'type' => 'RIGHT',
            'table' => Customer::class,
            'alias' => 't1',
            'on'    => [
                [
                    'column' => 't1>customerId',
                    'operator' => '=',
                    'value' => new Attribute('id'),
                    'glue' => 'AND'
                ]
            ]
        ], end($query->statements['joins']));
    }

    /**
     *
     */
    public function test_leftJoinEntity()
    {
        $query = Document::builder();
        $query->leftJoinEntity(Customer::class, 'customerId', 'id', 't1');

        $this->assertEquals([
            'type' => 'LEFT',
            'table' => Customer::class,
            'alias' => 't1',
            'on'    => [
                [
                    'column' => 't1>customerId',
                    'operator' => '=',
                    'value' => new Attribute('id'),
                    'glue' => 'AND'
                ]
            ]
        ], end($query->statements['joins']));
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SimpleConnection
     */
    private function createConnectionMock()
    {
        $connection = $this->getMockBuilder(SimpleConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['executeStatement', 'executeQuery', 'getDatabasePlatform', 'getDatabase', 'platform', 'factory'])
            ->getMock();
        $connection->expects($this->any())->method('getDatabasePlatform')->willReturn($this->repository->connection()->getDatabasePlatform());
        $connection->expects($this->any())->method('executeStatement')->willReturn(0);
        $connection->expects($this->any())->method('platform')->willReturn($this->repository->connection()->platform());
        $connection->expects($this->any())->method('factory')->willReturn($this->repository->connection()->factory());
        $connection->expects($this->any())->method('getDatabase')->willReturn($this->repository->connection()->getDatabase());

        return $connection;
    }

    /**
     * @see https://github.com/b2pweb/bdf-prime/issues/15
     */
    public function test_custom_alias_on_from()
    {
        /** @var Query $query */
        $query = User::where('name', ':like', 'J%');

        // Replace from
        $query->statements['tables'] = [];
        $query->from(User::metadata()->table, 'my_alias');

        $this->assertEquals(
            'SELECT my_alias.* FROM user_ my_alias WHERE my_alias.name_ LIKE ?',
            $query->toSql()
        );
    }

    /**
     *
     */
    public function test_change_from_alias()
    {
        $this->assertEquals(
            'SELECT my_alias.* FROM user_ my_alias WHERE my_alias.name_ LIKE ?',
            User::fromAlias('my_alias')->where('name', ':like', 'J%')->toSql()
        );
    }

    public function test_with_criteria()
    {
        /** @var CustomerCriteria $criteria */
        $criteria = Customer::criteria();

        $criteria
            ->name('foo')
            ->parentId(null)
        ;

        $query = Customer::where($criteria);

        $this->assertEquals('SELECT t0.* FROM customer_ t0 WHERE t0.name_ = \'foo\' AND t0.parent_id IS NULL', $query->toRawSql());
    }

    public function test_with_criteria_expression()
    {
        /** @var CustomerCriteria $criteria */
        $criteria = Customer::criteria();

        $criteria
            ->name((new Like('foo'))->startsWith())
            ->id(Operator::{'>'}(5))
        ;

        $query = Customer::where($criteria);

        $this->assertEquals('SELECT t0.* FROM customer_ t0 WHERE t0.name_ LIKE \'foo%\' AND t0.id_ > \'5\'', $query->toRawSql());
    }

    public function test_toCriteria_empty()
    {
        $this->assertSame([], $this->query->toCriteria());
    }

    public function test_toCriteria_simple()
    {
        $this->assertSame([
            'id' => 42,
            'name' => 'Robert',
        ], $this->query->where('id', 42)->where('name', 'Robert')->toCriteria());
    }

    public function test_toCriteria_simple_nested()
    {
        $this->assertSame([
            'id' => 42,
            'name' => 'Robert',
        ], $this->query->where(['id' => 42, 'name' => 'Robert'])->toCriteria());
    }

    public function test_toCriteria_not_supported()
    {
        $this->assertNull($this->query->where('id', '>', 42)->toCriteria());
        $this->assertNull($this->query->where('id', 42)->orWhere('name', 'foo')->toCriteria());
        $this->assertNull($this->query->where('id', [42, 45])->toCriteria());
        $this->assertNull(TestEntity::builder()
            ->where(['id' => 42, 'name' => 'Robert'])
            ->where(function (Query $query) {
                $query->where([
                    'value' => 'xxx',
                    'other' => 'yyy',
                ]);
            })
            ->toCriteria()
        );
    }

    public function test_toCriteria_with_filter()
    {
        $this->assertSame([
            'id' => 42,
            'name' => 'Robert',
        ], $this->query
            ->filter(fn (TestEntity $entity) => $entity->id === 42 && $entity->name === 'Robert')
            ->toCriteria());
    }
}
