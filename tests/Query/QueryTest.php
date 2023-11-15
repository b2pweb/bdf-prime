<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Cache\ArrayCache;
use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Cache\CacheKey;
use Bdf\Prime\Cache\DoctrineCacheAdapter;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\SqlCompiler;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Expression\Attribute;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\Query\Expression\Now;
use Bdf\Prime\Query\Expression\Raw;
use Bdf\Prime\Query\Factory\QueryFactoryInterface;
use Doctrine\DBAL\Cache\ArrayResult;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class QueryTest extends TestCase
{
    use PrimeTestCase;

    /**
     * 
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $connection = Prime::connection('test');
        $connection->schema()
            ->table('test_', function($table) {
                $table->bigint('id')->autoincrement()->primary();
                $table->string('name', 90)->nillable();
                $table->dateTime('date_insert')->nillable();
            })
            ->table('test_backup', function($table) {
                $table->bigint('id')->autoincrement()->primary();
                $table->string('name', 90)->nillable();
                $table->dateTime('date_insert')->nillable();
            })
            ->table('no_primary', function($table) {
                $table->string('foo')->nillable();
                $table->string('bar')->nillable();
            })
        ;
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {

    }

    /**
     * 
     */
    protected function tearDown(): void
    {
        $connection = Prime::connection('test');
        $connection->schema()
            ->drop('test_')
            ->drop('test_backup')
            ->drop('no_primary')
        ;

        $this->primeStop();
    }

    /**
     * @return Query
     */
    public function query()
    {
        return Prime::connection('test')->from('test_');
    }
    
    /**
     * 
     */
    public function push($entity)
    {
        $this->query()->insert((array)$entity);
    }

    /**
     * 
     */
    public function test_set_get_cache()
    {
        $cache = $this->createMock(CacheInterface::class);
        
        $query = $this->query();
        $query->setCache($cache);
        
        $this->assertSame($cache, $query->cache());
    }

    /**
     * 
     */
    public function test_set_get_connection()
    {
        $compiler = $this->createMock(SqlCompiler::class);
        $connection = $this->getMockBuilder(SimpleConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factory = $this->createMock(QueryFactoryInterface::class);
        $factory->expects($this->any())->method('compiler')->will($this->returnValue($compiler));
        $connection->expects($this->any())->method('factory')->will($this->returnValue($factory));

        $query = $this->query();
        $query->on($connection);
        
        $this->assertSame($connection, $query->connection());
        $this->assertSame($compiler, $query->compiler());
    }

    /**
     * 
     */
    public function test_insert()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1'
        ]);
        
        $this->assertEquals(1, $this->query()->count());
    }
    
    /**
     * 
     */
    public function test_delete()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1'
        ]);
        
        $this->query()->delete();
        
        $this->assertEquals(0, $this->query()->count());
    }
    
    /**
     * 
     */
    public function test_update()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1'
        ]);
        
        $this->query()->where('id', '=', 1)->update(['name' => new Raw('id')]);
        
        $this->assertEquals('1', $this->query()->first()['name']);
    }
    
    /**
     * 
     */
    public function test_select_all()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'test-name2'
        ]);
        
        $query = $this->query();
        $result = $query->all();
        
        $this->assertEquals('SELECT * FROM test_', $query->toSql());
        $this->assertEquals(2, count($result));
        $this->assertEquals('test-name1', $result[0]['name']);
        $this->assertEquals('test-name2', $result[1]['name']);
    }
    
    /**
     * 
     */
    public function test_select_column_values()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'test-name2'
        ]);
        
        $query = $this->query();
        $result = $query->distinct()->inRows('name');
        
        $this->assertEquals('SELECT DISTINCT name FROM test_', $query->toSql());
        $this->assertEquals(2, count($result));
        $this->assertEquals('test-name1', $result[0]);
        $this->assertEquals('test-name2', $result[1]);
    }
    
    /**
     * 
     */
    public function test_select_column_value()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'test-name2'
        ]);
        
        $query = $this->query();
        $result = $query->distinct()->inRow('name');
        
        $this->assertEquals('SELECT DISTINCT name FROM test_ LIMIT 1', $query->toSql());
        $this->assertEquals('test-name1', $result);
    }
    
    /**
     * 
     */
    public function test_count()
    {
        $this->assertEquals(0, $this->query()->count());
        
        $this->push([
            'id'   => 1,
            'name' => 'test-name'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'test-name'
        ]);
        
        $this->assertEquals(2, $this->query()->count());
        $this->assertEquals(1, $this->query()->distinct()->count('name'));
        $this->assertEquals(1, $this->query()->whereRaw('id > 1')->count());
    }
    
    /**
     * 
     */
    public function test_post_processor()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name'
        ]);
        
        $entity = $this->query()->post(function($rows) {
            return $rows->asObject()->all();
        }, false)->first();
        
        $this->assertEquals(1, $entity->id);
        $this->assertEquals('test-name', $entity->name);
    }
    
    /**
     * 
     */
    public function test_each_processor()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name'
        ]);
        
        $entity = $this->query()->post(function($row) {
            return (object)$row;
        }, true)->first();
        
        $this->assertEquals(1, $entity->id);
        $this->assertEquals('test-name', $entity->name);
    }
    
    /**
     *
     */
    public function test_where_with_operator()
    {
        $query = $this->query()->where('id', '>', 1);
        
        $this->assertEquals('SELECT * FROM test_ WHERE id > ?', $query->toSql());
        $this->assertEquals('SELECT * FROM test_ WHERE id > 1', $query->toRawSql());
    }

    /**
     *
     */
    public function test_where_with_column_which_is_a_callable_string_should_be_used_as_column()
    {
        $query = $this->query()->where('key', 1);

        $this->assertEquals('SELECT * FROM test_ WHERE key = ?', $query->toSql());
        $this->assertEquals('SELECT * FROM test_ WHERE key = 1', $query->toRawSql());
    }

    /**
     * 
     */
    public function test_where_without_operator()
    {
        $query = $this->query()->where('id', 1);
        
        $this->assertEquals('SELECT * FROM test_ WHERE id = ?', $query->toSql());
        $this->assertEquals('SELECT * FROM test_ WHERE id = 1', $query->toRawSql());
    }
    
    /**
     * 
     */
    public function test_quote_identifier()
    {
        $query = $this->query()->where('id', '>', 1);
        $query->useQuoteIdentifier();

        $this->assertEquals('SELECT * FROM "test_" WHERE "id" > ?', $query->toSql());
    }
    
    /**
     * 
     */
    public function test_where_with_null()
    {
        $query = $this->query()->where('id', '=', null);
        
        $this->assertEquals('SELECT * FROM test_ WHERE id IS NULL', $query->toSql());
    }

    /**
     *
     */
    public function test_where_null_without_operator()
    {
        $query = $this->query()->where('id');

        $this->assertEquals('SELECT * FROM test_ WHERE id IS NULL', $query->toSql());
    }

    /**
     *
     */
    public function test_where_null_by_array()
    {
        $query = $this->query()->where(['id' => null]);

        $this->assertEquals('SELECT * FROM test_ WHERE id IS NULL', $query->toSql());
    }

    /**
     *
     */
    public function test_where_with_not_null()
    {
        $query = $this->query()->where('id', '!=', null);

        $this->assertEquals('SELECT * FROM test_ WHERE id IS NOT NULL', $query->toSql());
    }

    /**
     *
     */
    public function test_where_null()
    {
        $query = $this->query()->whereNull('id');

        $this->assertEquals('SELECT * FROM test_ WHERE id IS NULL', $query->toSql());
    }

    /**
     *
     */
    public function test_where_not_null()
    {
        $query = $this->query()->whereNotNull('id');

        $this->assertEquals('SELECT * FROM test_ WHERE id IS NOT NULL', $query->toSql());
    }

    /**
     *
     */
    public function test_where_raw_string_not_allowed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->query()->where([['a']]);
    }

    /**
     *
     */
    public function test_where_in_with_null()
    {
        $query = $this->query()->where('id', ':in', [null]);
        $this->assertEquals('SELECT * FROM test_ WHERE id IS NULL', $query->toRawSql());

        $query = $this->query()->where('id', ':in', [1, null]);
        $this->assertEquals('SELECT * FROM test_ WHERE (id IN (1) OR id IS NULL)', $query->toRawSql());

        $query = $this->query()->where('id', ':in', [1, 2]);
        $this->assertEquals('SELECT * FROM test_ WHERE id IN (1,2)', $query->toRawSql());
    }

    /**
     *
     */
    public function test_where_notin_with_null()
    {
        $query = $this->query()->where('id', '!in', [null]);
        $this->assertEquals('SELECT * FROM test_ WHERE id IS NOT NULL', $query->toRawSql());

        $query = $this->query()->where('id', '!in', [1, null]);
        $this->assertEquals('SELECT * FROM test_ WHERE (id NOT IN (1) AND id IS NOT NULL)', $query->toRawSql());

        $query = $this->query()->where('id', '!in', [1, 2]);
        $this->assertEquals('SELECT * FROM test_ WHERE id NOT IN (1,2)', $query->toRawSql());
    }

    /**
     *
     */
    public function test_where_in_complex_type()
    {
        $date1 = \DateTime::createFromFormat('Y-m-d H:i:s', '2016-07-18 00:00:00');
        $date2 = \DateTime::createFromFormat('Y-m-d H:i:s', '2016-07-19 00:00:00');

        $query = $this->query()->where('date_insert', 'in', [$date1, $date2]);
        $this->assertEquals('SELECT * FROM test_ WHERE date_insert IN (\'2016-07-18 00:00:00\',\'2016-07-19 00:00:00\')', $query->toRawSql());
    }

    /**
     *
     */
    public function test_where_in_complex_type_func()
    {
        $date1 = \DateTime::createFromFormat('Y-m-d H:i:s', '2016-07-18 00:00:00');
        $date2 = \DateTime::createFromFormat('Y-m-d H:i:s', '2016-07-19 00:00:00');

        $this->push([
            'id'          => 10,
            'name'        => 'name1',
            'date_insert' => $date2,
        ]);
        $this->push([
            'id'   => 20,
            'name' => 'name2'
        ]);

        $result = $this->query()->where('date_insert', 'in', [$date1, $date2])->all();

        $this->assertEquals(1, count($result));
        $this->assertEquals(10, $result[0]['id']);
    }

    /**
     *
     */
    public function test_or_where_null()
    {
        $query = $this->query()->where('id', 1)->orWhereNull('id');

        $this->assertEquals('SELECT * FROM test_ WHERE id = 1 OR id IS NULL', $query->toRawSql());
    }

    /**
     *
     */
    public function test_or_where_with_column_name_which_is_a_callable_string_should_be_used_as_string()
    {
        $query = $this->query()->where('id', 1)->orWhere('key', 5);

        $this->assertEquals('SELECT * FROM test_ WHERE id = 1 OR key = 5', $query->toRawSql());
    }

    /**
     *
     */
    public function test_or_where_not_null()
    {
        $query = $this->query()->where('id', 1)->orWhereNotNull('id', '!=', null);

        $this->assertEquals('SELECT * FROM test_ WHERE id = 1 OR id IS NOT NULL', $query->toRawSql());
    }

    /**
     * 
     */
    public function test_where_with_multiple()
    {
        $query = $this->query()->where([
            'id'      => 1,
            'name !=' => 'test'
        ]);
        
        $this->assertEquals('SELECT * FROM test_ WHERE id = ? AND name != ?', $query->toSql());
        $this->assertEquals('SELECT * FROM test_ WHERE id = 1 AND name != \'test\'', $query->toRawSql());
    }
    
    /**
     * 
     */
    public function test_two_where()
    {
        $query = $this->query()
            ->where('id', '=', 1)
            ->where('name', '!=', 'test');
        
        $this->assertEquals('SELECT * FROM test_ WHERE id = ? AND name != ?', $query->toSql());
        $this->assertEquals('SELECT * FROM test_ WHERE id = 1 AND name != \'test\'', $query->toRawSql());
    }
    
    /**
     * 
     */
    public function test_or_where()
    {
        $query = $this->query()
            ->orWhere('id', '=', 1)
            ->orWhere('name', '!=', 'test');
        
        $this->assertEquals('SELECT * FROM test_ WHERE id = ? OR name != ?', $query->toSql());
        $this->assertEquals('SELECT * FROM test_ WHERE id = 1 OR name != \'test\'', $query->toRawSql());
    }

    /**
     *
     */
    public function test_where_raw()
    {
        $query = $this->query()->whereRaw('id BETWEEN ...');

        $this->assertEquals('SELECT * FROM test_ WHERE id BETWEEN ...', $query->toSql());
    }

    /**
     *
     */
    public function test_or_where_raw()
    {
        $query = $this->query()->orWhereRaw('id = 1')->orWhereRaw('id BETWEEN ...');

        $this->assertEquals('SELECT * FROM test_ WHERE id = 1 OR id BETWEEN ...', $query->toSql());
    }

    /**
     * 
     */
    public function test_complete_where()
    {
        $query = $this->query()
            ->where([
                'id'      => 1,
                'name !=' => 'test'
            ])
            ->orWhere('name', '=', 'value')
        ;
        
        $this->assertEquals('SELECT * FROM test_ WHERE (id = ? AND name != ?) OR name = ?', $query->toSql());
        $this->assertEquals('SELECT * FROM test_ WHERE (id = 1 AND name != \'test\') OR name = \'value\'', $query->toRawSql());
    }
    
    /**
     * 
     */
    public function test_nested_where()
    {
        $query = $this->query()
            ->nested(function($query) {
                $query->where('id', '!=', 2);
                $query->nested(function($query) {
                    $query->where('id', '!=', 3);
                    $query->orWhere('id', '!=', 4);
                });
            })
            ->where([
                'id'      => 1,
                'name !=' => 'test'
            ])
            ->orWhere('name', '=', 'value')
        ;
        
        $this->assertEquals('SELECT * FROM test_ WHERE (id != ? AND (id != ? OR id != ?)) AND (id = ? AND name != ?) OR name = ?', $query->toSql());
        $this->assertEquals('SELECT * FROM test_ WHERE (id != 2 AND (id != 3 OR id != 4)) AND (id = 1 AND name != \'test\') OR name = \'value\'', $query->toRawSql());
    }

    /**
     *
     */
    public function test_having_or()
    {
        $query = $this->query()->orHaving('id', 1)->orHaving('id');

        $this->assertEquals('SELECT * FROM test_ HAVING id = ? OR id IS NULL', $query->toSql());
    }

    /**
     *
     */
    public function test_having_or_by_array()
    {
        $query = $this->query()->having([
            'name :like' => 'Test%',
            'id' => 2,
        ], 'OR');

        $this->assertEquals('SELECT * FROM test_ HAVING name LIKE ? OR id = ?', $query->toSql());
    }

    /**
     *
     */
    public function test_having_null()
    {
        $query = $this->query()->havingNull('id');

        $this->assertEquals('SELECT * FROM test_ HAVING id IS NULL', $query->toSql());
    }

    /**
     *
     */
    public function test_or_having_null()
    {
        $query = $this->query()->orHaving('id', 1)->orHavingNull('id');

        $this->assertEquals('SELECT * FROM test_ HAVING id = ? OR id IS NULL', $query->toSql());
    }

    /**
     *
     */
    public function test_having_not_null()
    {
        $query = $this->query()->havingNotNull('id');

        $this->assertEquals('SELECT * FROM test_ HAVING id IS NOT NULL', $query->toSql());
    }

    /**
     *
     */
    public function test_or_having_not_null()
    {
        $query = $this->query()->orHaving('id', 1)->orHavingNotNull('id');

        $this->assertEquals('SELECT * FROM test_ HAVING id = ? OR id IS NOT NULL', $query->toSql());
    }

    /**
     *
     */
    public function test_having_raw()
    {
        $query = $this->query()->havingRaw('id BETWEEN ...');

        $this->assertEquals('SELECT * FROM test_ HAVING id BETWEEN ...', $query->toSql());
    }

    /**
     *
     */
    public function test_or_having_raw()
    {
        $query = $this->query()->orHavingRaw('id = 1')->orHavingRaw('id BETWEEN ...');

        $this->assertEquals('SELECT * FROM test_ HAVING id = 1 OR id BETWEEN ...', $query->toSql());
    }

    /**
     * 
     */
    public function test_compile_in_expression()
    {
        $query = $this->query()->where('id', ':in', [1, 2]);
        
        $this->assertEquals('SELECT * FROM test_ WHERE id IN (?,?)', $query->toSql());
        $this->assertEquals('SELECT * FROM test_ WHERE id IN (1,2)', $query->toRawSql());
    }

    /**
     *
     */
    public function test_compile_in_with_scalar_value()
    {
        $query = $this->query()->where('id', 'in', 'test');

        $this->assertEquals('SELECT * FROM test_ WHERE id IN (?)', $query->toSql());
        $this->assertEquals(['test'], $query->getBindings());
    }

    /**
     * 
     */
    public function test_compile_is_null_expression()
    {
        $query = $this->query()->where('id', ':in', []);
        $this->assertEquals('SELECT * FROM test_ WHERE id IS NULL', $query->toSql());
        
        $query = $this->query()->where('id', '=', []);
        $this->assertEquals('SELECT * FROM test_ WHERE id IS NULL', $query->toSql());
    }
    
    /**
     * 
     */
    public function test_compile_is_not_null_expression()
    {
        $query = $this->query()->where('id', ':notin', []);
        $this->assertEquals('SELECT * FROM test_ WHERE id IS NOT NULL', $query->toSql());
        
        $query = $this->query()->where('id', '!=', []);
        $this->assertEquals('SELECT * FROM test_ WHERE id IS NOT NULL', $query->toSql());
    }
    
    /**
     * 
     */
    public function test_compile_into_expression()
    {
        $query = $this->query()
            ->where('id', ':like', ['1', '2']);
        
        $this->assertEquals('SELECT * FROM test_ WHERE (id LIKE ? OR id LIKE ?)', $query->toSql());
        $this->assertEquals('SELECT * FROM test_ WHERE (id LIKE \'1\' OR id LIKE \'2\')', $query->toRawSql());
    }
    
    /**
     * 
     */
    public function test_compile_sub_query()
    {
        $query = $this->query()->where('id', ':in', $this->query()->select('id')->where('id', '>=', 2));
        
        $this->assertEquals('SELECT * FROM test_ WHERE id IN (SELECT id FROM test_ WHERE id >= ?)', $query->toSql());
    }
    
    /**
     * 
     */
    public function test_compile_expression()
    {
        $query = $this->query()->where('id', '=', new Now());
        
        $this->assertEquals('SELECT * FROM test_ WHERE id = CURRENT_DATE', $query->toSql());
    }
    
    /**
     * 
     */
    public function test_special_command()
    {
        $query = $this->query()->where([
            'id'     => 1,
            ':limit' => 1,
        ]);
        
        $this->assertEquals('SELECT * FROM test_ WHERE id = ? LIMIT 1', $query->toSql());
    }
    
    /**
     * 
     */
    public function test_custom_filter()
    {
        $query = $this->query()
            ->addCustomFilter('nameLike', function($query, $value) {
                $query->where('name', ':like', $value);
            })
            ->where('nameLike', '=', 'test');
        
        $this->assertEquals('SELECT * FROM test_ WHERE name LIKE ?', $query->toSql());
    }
    
    /**
     * 
     */
    public function test_custom_filter_in_array()
    {
        $query = $this->query()
            ->addCustomFilter('nameLike', function($query, $value) {
                $query->where('name', ':like', $value);
            })
            ->where(['nameLike' => 'test']);
        
        $this->assertEquals('SELECT * FROM test_ WHERE name LIKE ?', $query->toSql());
    }

    /**
     *
     */
    public function test_between()
    {
        $query = $this->query()
            ->where(['id :between' => [1,3]]);

        $this->assertEquals("SELECT * FROM test_ WHERE id BETWEEN ? AND ?", $query->toSql());
        $this->assertEquals([1, 3], $query->getBindings());
    }

    /**
     *
     */
    public function test_between_functional()
    {
        $this->push([
            'id'   => 1,
            'name' => 'Bob'
        ]);
        $this->push([
            'id'   => 3,
            'name' => 'George'
        ]);
        $this->push([
            'id'   => 6,
            'name' => 'Paul'
        ]);

        $this->assertEquals(['George', 'Paul'], $this->query()->where(['id :between' => [2, 8]])->inRows('name'));
        $this->assertEquals(['Bob', 'George'], $this->query()->where(['id :between' => [1, 5]])->inRows('name'));
        $this->assertEquals(['Bob', 'George'], $this->query()->where('name', ':between', ['A', 'K'])->inRows('name'));
    }

    /**
     *
     */
    public function test_not_between()
    {
        $query = $this->query()
            ->where(['id :notbetween' => [1,3]]);

        $this->assertEquals("SELECT * FROM test_ WHERE NOT(id BETWEEN ? AND ?)", $query->toSql());
        $this->assertEquals([1, 3], $query->getBindings());
    }

    /**
     *
     */
    public function test_not_like()
    {
        $this->assertEquals("SELECT * FROM test_ WHERE name NOT LIKE ?", $this->query()->where('name', '!like', 'B%')->toSql());
        $this->assertEquals("SELECT * FROM test_ WHERE name NOT LIKE ?", $this->query()->where('name', ':notlike', 'B%')->toSql());
        $this->assertEquals("SELECT * FROM test_ WHERE (name NOT LIKE ? AND name NOT LIKE ?)", $this->query()->where('name', '!like', ['B%', 'C%'])->toSql());

        $this->push([
            'id'   => 1,
            'name' => 'Bob'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'George'
        ]);

        $this->assertEquals([2], $this->query()->where('name', '!like', 'B%')->inRows('id'));
        $this->assertEquals([], $this->query()->where('name', '!like', ['B%', 'G%'])->inRows('id'));
    }

    /**
     *
     */
    public function test_wrapper()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name'
        ]);
        
        $collection = $this->query()->wrapAs('array')->all();

        $this->assertInstanceOf('Bdf\Prime\Collection\ArrayCollection', $collection);
        $this->assertEquals(1, $collection->get(0)['id']);
    }

    /**
     *
     */
    public function test_pagination()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'test-name2'
        ]);
        
        $collection = $this->query()->paginate(1);

        $this->assertInstanceOf('Bdf\Prime\Query\Pagination\PaginatorInterface', $collection);
        $this->assertEquals(2, $collection->size());
        $this->assertEquals(1, $collection->count());
        $this->assertEquals('test-name1', $collection->get(0)['name']);
    }
    
    /**
     *
     */
    public function test_pagination_on_complete_collection()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'test-name2'
        ]);
        
        $collection = $this->query()->paginate();

        $this->assertInstanceOf('Bdf\Prime\Query\Pagination\PaginatorInterface', $collection);
        $this->assertEquals(2, $collection->size());
        $this->assertEquals(2, $collection->count());
        $this->assertEquals('test-name1', $collection->get(0)['name']);
        $this->assertEquals('test-name2', $collection->get(1)['name']);
    }

    /**
     *
     */
    public function test_complex_count_pagination()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'test-name2'
        ]);
        $this->push([
            'id'   => 3,
            'name' => 'test-name2'
        ]);
        $this->push([
            'id'   => 4,
            'name' => null
        ]);

        $this->assertEquals(4, $this->query()->select('name')->paginationCount());
        $this->assertEquals(3, $this->query()->select('name')->distinct()->paginationCount());
        $this->assertEquals(3, $this->query()->group('name')->paginationCount());
        $this->assertEquals(3, $this->query()->group('name', 'id')->paginationCount());

        $this->assertEquals(3, $this->query()->count('name'));
        $this->assertEquals(2, $this->query()->distinct()->count('name'));
    }

    /**
     *
     */
    public function test_walker()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'test-name2'
        ]);
        
        $collection = $this->query()->walk(1);

        $this->assertInstanceOf('Bdf\Prime\Query\Pagination\PaginatorInterface', $collection);
        $this->assertEquals(2, $collection->size());
        
        $i = 0;
        foreach ($collection as $entity) {
            $i++;
            $this->assertEquals('test-name'.$i, $entity['name']);
        }
        
        $this->assertEquals(2, $i, 'assert the collection was walked');
    }

    /**
     *
     */
    public function test_walker_by_iterator()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'test-name2'
        ]);

        $i = 0;
        foreach ($this->query() as $entity) {
            $i++;
            $this->assertEquals('test-name'.$i, $entity['name']);
        }

        $this->assertEquals(2, $i, 'assert the collection was walked');
    }

    /**
     *
     */
    public function test_where_transformer_like()
    {
        $query = $this->query()->where('roles', (new Like([1, 3]))->searchableArray());

        $this->assertEquals('SELECT * FROM test_ WHERE (roles LIKE ? OR roles LIKE ?)', $query->toSql());

        $this->assertEquals([
            '%,1,%',
            '%,3,%'
        ], $query->getBindings());
    }

    /**
     *
     */
    public function test_write_lock()
    {
        // Set server version to ensure that no connection will be created for detecting the version
        Prime::service()->connections()->declareConnection('mysql', ['adapter' => 'mysql', 'serverVersion' => '5.6']);

        $query = Prime::connection('mysql')->from('test_')->lock();
        $this->assertEquals("SELECT * FROM test_ FOR UPDATE", $query->toSql());

        Prime::service()->connections()->removeConnection('mysql');
    }

    /**
     *
     */
    public function test_read_lock()
    {
        Prime::service()->connections()->declareConnection('mysql', ['adapter' => 'mysql', 'serverVersion' => '5.6']);

        $query = Prime::connection('mysql')->from('test_')->lock(2);
        $this->assertEquals("SELECT * FROM test_ LOCK IN SHARE MODE", $query->toSql());

        Prime::service()->connections()->removeConnection('mysql');
    }

    /**
     *
     */
    public function test_no_lock_on_aggregate()
    {
        Prime::service()->connections()->declareConnection('mysql', ['adapter' => 'mysql', 'serverVersion' => '5.6']);
        $mysql = Prime::service()->connections()->getConnection('mysql');

        $connection = $this->getMockBuilder(SimpleConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['executeUpdate', 'executeQuery', 'getDatabasePlatform', 'getDatabase', 'platform', 'factory'])
            ->getMock();
        $connection->expects($this->any())->method('getDatabasePlatform')->willReturn($mysql->getDatabasePlatform());
        $connection->expects($this->any())->method('getDatabase')->willReturn('my database');
        $connection->expects($this->any())->method('platform')->willReturn($mysql->platform());
        $connection->expects($this->any())->method('factory')->willReturn($mysql->factory());
        $connection->expects($this->once())->method('executeQuery')
            ->with("SELECT COUNT(*) AS aggregate FROM test_")
            ->will($this->returnValue(new Result(new ArrayResult([['aggregate' => 1]]), $connection)));

        $query = new Query($connection);
        $query->from('test_')->lock()->count();

        Prime::service()->connections()->removeConnection('mysql');
    }

    /**
     *
     */
    public function test_is_lock()
    {
        $query = $this->query();
        $this->assertFalse($query->isLocked());

        $query->lock();
        $this->assertTrue($query->isLocked());
        $this->assertFalse($query->isLocked(2));
    }

    /**
     *
     */
    public function test_useQuoteIdentifier()
    {
        $query = $this->query()->where(['id :between' => [1,3]]);
        $query->useQuoteIdentifier();

        $this->assertTrue($query->isQuoteIdentifier());
        $this->assertEquals('SELECT * FROM "test_" WHERE "id" BETWEEN ? AND ?', $query->toSql());
    }

    /**
     *
     */
    public function test_select_then_delete()
    {
        $this->push([
            'id' => 1,
            'name' => 'test-name1'
        ]);

        $query = $this->query()->where('id', 1);

        $this->assertEquals([
            [
                'id' => 1,
                'name' => 'test-name1',
            ],
        ], $query->all(['id', 'name']));

        $this->assertEquals('SELECT id, name FROM test_ WHERE id = ?', $query->compile());

        $this->assertEquals(1, $query->delete());
        $this->assertEquals('DELETE FROM test_ WHERE id = ?', $query->compile());

        $this->assertSame([], $query->all(['id', 'name']));
        $this->assertEquals('SELECT id, name FROM test_ WHERE id = ?', $query->compile());
    }

    /**
     *
     */
    public function test_compile_unsupported_type()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The query Bdf\Prime\Query\Query do not supports type invalid');

        $query = $this->query();

        $r = new \ReflectionProperty(Query::class, 'type');
        $r->setAccessible(true);
        $r->setValue($query, 'invalid');

        $query->compile();
    }

    /**
     *
     */
    public function test_compile_invalid_compiler()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The query Bdf\Prime\Query\Query do not supports type select');

        $query = $this->query();

        $r = new \ReflectionProperty(Query::class, 'compiler');
        $r->setAccessible(true);
        $r->setValue($query, new \stdClass());

        $query->compile();
    }

    /**
     *
     */
    public function test_compile_select()
    {
        $query = $this->query();

        $this->assertEquals(Compilable::TYPE_SELECT, $query->type());
        $this->assertEquals('SELECT * FROM test_', $query->compile());
        $this->assertEquals('SELECT * FROM test_', $query->compile(true));
    }

    /**
     *
     */
    public function test_compile_delete()
    {
        $query = $this->query();
        $query->delete();

        $this->assertEquals(Compilable::TYPE_DELETE, $query->type());
        $this->assertEquals('DELETE FROM test_', $query->compile());
        $this->assertEquals('DELETE FROM test_', $query->compile(true));
    }

    /**
     *
     */
    public function test_compile_update()
    {
        $query = $this->query();
        $query->update(['name' => 1]);

        $this->assertEquals(Compilable::TYPE_UPDATE, $query->type());
        $this->assertEquals('UPDATE test_ SET name = ?', $query->compile());
        $this->assertEquals('UPDATE test_ SET name = ?', $query->compile(true));
    }

    /**
     *
     */
    public function test_compile_insert()
    {
        $query = $this->query();
        $query->insert(['name' => 1]);

        $this->assertEquals(Compilable::TYPE_INSERT, $query->type());
        $this->assertEquals('INSERT INTO test_ (name) VALUES(?)', $query->compile());
        $this->assertEquals('INSERT INTO test_ (name) VALUES(?)', $query->compile(true));
    }

    /**
     *
     */
    public function test_execute_with_cache()
    {
        $this->push([
            'id' => 1,
            'name' => 'test-name1'
        ]);

        $cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ArrayCache());
        $query = $this->query();
        $query->setCache($cache)->useCache();

        $this->assertEquals([[
            'id' => '1',
            'name' => 'test-name1',
            'date_insert' => null
        ]], $query->execute()->all());

        $this->assertEquals([[
            'id' => '1',
            'name' => 'test-name1',
            'date_insert' => null
        ]], $cache->get(new CacheKey('test:test_', sha1('SELECT * FROM test_-a:0:{}'))));

        $this->push([
            'id' => 2,
            'name' => 'test-name2'
        ]);

        $this->assertEquals([[
            'id' => '1',
            'name' => 'test-name1',
            'date_insert' => null
        ]], $query->execute()->all());
    }

    /**
     *
     */
    public function test_update_will_clear_cache()
    {
        $this->push([
            'id' => 1,
            'name' => 'test-name1'
        ]);

        $cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ArrayCache());
        $query = $this->query();
        $query->setCache($cache)->useCache();

        $this->assertEquals([[
            'id' => '1',
            'name' => 'test-name1',
            'date_insert' => null
        ]], $query->execute()->all());
        $this->assertNotNull($cache->get(new CacheKey('test:test_', sha1('SELECT * FROM test_-a:0:{}'))));

        $query->update(['name' => 'new-name']);
        $this->assertNull($cache->get(new CacheKey('test:test_', sha1('SELECT * FROM test_-a:0:{}'))));
    }

    /**
     *
     */
    public function test_paginationCount_distinct()
    {
        Prime::connection('test')->insert('no_primary', [
            'foo' => 'bar',
            'bar' => 'baz',
        ]);
        Prime::connection('test')->insert('no_primary', [
            'foo' => 'bar',
            'bar' => 'baz',
        ]);
        Prime::connection('test')->insert('no_primary', [
            'foo' => 'oof',
            'bar' => 'rab',
        ]);

        $query = Prime::connection('test')->builder()->from('no_primary')->distinct();

        // Impossible de tester le SQL généré
        $this->assertEquals(2, $query->paginationCount());
    }

    /**
     *
     */
    public function test_insert_select()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1',
            'date_insert' => new \DateTime(),
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'test-name2',
            'date_insert' => new \DateTime(),
        ]);

        $query = Prime::connection('test')->from('test_backup')->values($this->query());

        $this->assertEquals(2, $query->insert());
        $this->assertEquals('INSERT INTO test_backup SELECT * FROM test_', $query->toSql());

        $this->assertEquals(
            $this->query()->all(),
            Prime::connection('test')->from('test_backup')->all()
        );
    }

    /**
     *
     */
    public function test_replace_select()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1',
            'date_insert' => new \DateTime(),
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'test-name2',
            'date_insert' => new \DateTime(),
        ]);

        Prime::connection('test')->from('test_backup')->values($this->query())->insert();

        $this->query()->where('id', 1)->update(['name' => 'new-name']);

        $query = Prime::connection('test')->from('test_backup')->values($this->query());

        $this->assertEquals(2, $query->replace());
        $this->assertEquals('REPLACE INTO test_backup SELECT * FROM test_', $query->toSql());

        $this->assertEquals($this->query()->all(), Prime::connection('test')->from('test_backup')->all());
        $this->assertEquals('new-name', Prime::connection('test')->from('test_backup')->where('id', 1)->inRow('name'));
    }

    /**
     *
     */
    public function test_insert_select_with_column()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1',
            'date_insert' => new \DateTime(),
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'test-name2',
            'date_insert' => new \DateTime(),
        ]);

        $query = Prime::connection('test')->from('test_backup')->values($this->query()->select(['id', 'name']));

        $this->assertEquals(2, $query->insert());
        $this->assertEquals('INSERT INTO test_backup (id, name) SELECT id as id, name as name FROM test_', $query->toSql());

        $this->assertEquals([
            [
                'id'   => 1,
                'name' => 'test-name1',
                'date_insert' => null,
            ],
            [
                'id'   => 2,
                'name' => 'test-name2',
                'date_insert' => null,
            ],
        ], Prime::connection('test')->from('test_backup')->all());
    }

    /**
     *
     */
    public function test_insert_select_between_incompatible_table_with_column_mapping()
    {
        $this->push([
            'id'   => 1,
            'name' => 'test-name1',
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'test-name2',
        ]);

        $query = Prime::connection('test')->from('no_primary')->values($this->query()->select(['foo' => 'id', 'bar' => 'name']));

        $this->assertEquals(2, $query->insert());
        $this->assertEquals('INSERT INTO no_primary (foo, bar) SELECT id as foo, name as bar FROM test_', $query->toSql());

        $this->assertEquals([
            [
                'foo' => 1,
                'bar' => 'test-name1',
            ],
            [
                'foo' => 2,
                'bar' => 'test-name2',
            ],
        ], Prime::connection('test')->from('no_primary')->all());
    }

    /**
     *
     */
    public function test_select_will_fill_cache()
    {
        $this->push([
            'id' => 1,
            'name' => 'John'
        ]);

        $expected = [[
            'id' => 1,
            'name' => 'John'
        ]];

        $cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ArrayCache());

        $query = $this->query();
        $query->setCache($cache)->useCache();

        $this->assertEquals($expected, $query->execute(['id', 'name'])->all());
        $this->assertEquals($expected, $cache->get(new CacheKey('test:test_', sha1('SELECT id, name FROM test_-a:0:{}'))));
    }

    /**
     *
     */
    public function test_select_cached()
    {
        $this->push([
            'id' => 1,
            'name' => 'John'
        ]);

        $expected = [[
            'id' => 1,
            'name' => 'John'
        ]];

        $cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ArrayCache());

        $query = $this->query();
        $query->setCache($cache)->useCache();
        $this->assertEquals($expected, $query->execute(['id', 'name'])->all());

        // insert without clear cache
        $this->push([
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->assertEquals($expected, $query->execute(['id', 'name'])->all());

        $query->setCacheKey(null);

        $this->assertEquals([
            [
                'id' => 1,
                'name' => 'John'
            ],
            [
                'id' => 2,
                'name' => 'Mickey'
            ],
        ], $query->execute(['id', 'name'])->all());
    }

    /**
     *
     */
    public function test_cache()
    {
        $this->push([
            'id' => 1,
            'name' => 'test-name1'
        ]);

        $cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ArrayCache());
        $query = $this->query();
        $query
            ->setCache($cache)
            ->useCache()
        ;

        $this->assertEquals('test:test_', $query->getCacheKey()->namespace());
        $this->assertEquals(sha1('SELECT * FROM test_-a:0:{}'), $query->getCacheKey()->key());
        $this->assertEquals(0, $query->getCacheKey()->lifetime());

        $this->assertEquals('my-ns', $query->setCacheNamespace('my-ns')->getCacheKey()->namespace());
        $this->assertEquals('my-key', $query->setCacheKey('my-key')->getCacheKey()->key());
        $this->assertEquals(100, $query->setCacheLifetime(100)->getCacheKey()->lifetime());

        $result = $query->execute()->all();

        $this->assertEquals($result, $cache->get($query->getCacheKey()));
    }

    /**
     *
     */
    public function test_useCache()
    {
        $this->push([
            'id' => 1,
            'name' => 'test-name1'
        ]);

        $cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ArrayCache());
        $query = $this->query();
        $query
            ->setCache($cache)
            ->useCache(100, 'my-key')
        ;

        $this->assertEquals('test:test_', $query->getCacheKey()->namespace());
        $this->assertEquals('my-key', $query->getCacheKey()->key());
        $this->assertEquals(100, $query->getCacheKey()->lifetime());

        $query->useCache(500, 'other-key');

        $this->assertEquals('test:test_', $query->getCacheKey()->namespace());
        $this->assertEquals('other-key', $query->getCacheKey()->key());
        $this->assertEquals(500, $query->getCacheKey()->lifetime());
    }

    /**
     *
     */
    public function test_fromAlias_define_alias_for_last_table()
    {
        $this->assertEquals('SELECT * FROM test_ my_alias', $this->query()->fromAlias('my_alias')->toSql());
    }

    /**
     *
     */
    public function test_fromAlias_with_table_name()
    {
        $this->assertEquals('SELECT * FROM no_primary, test_ my_alias', $this->query()->from('no_primary')->fromAlias('my_alias', 'test_')->toSql());
    }

    /**
     *
     */
    public function test_fromAlias_redefine_alias()
    {
        $this->assertEquals('SELECT * FROM test_, no_primary my_alias', $this->query()->from('no_primary', 'np')->fromAlias('my_alias', 'np')->toSql());
    }

    /**
     *
     */
    public function test_join_with_subQuery()
    {
        $subQuery = Prime::connection('test')->from('test_', 'sub')->select(['name' => 'sub.name', 'id' => new Raw('(sub.id * 2)')])->where('sub.date_insert', '>', 1000);
        $query = Prime::connection('test')->from('test_', 'm')->addSelect(['m.*', 'jName' => 'j.name'])->join([$subQuery, 'j'], 'm.id', '=', new Attribute('j.id'))->where('m.name', ':like', '%foo%');

        $this->assertEquals(
            'SELECT m.*, j.name as jName FROM test_ m INNER JOIN (SELECT sub.name as name, (sub.id * 2) as id FROM test_ sub WHERE sub.date_insert > ?) as j ON m.id = j.id WHERE m.name LIKE ?',
            $query->toSql()
        );

        $this->assertEquals([1000, '%foo%'], $query->getBindings());
    }

    /**
     *
     */
    public function test_join_with_fk_which_is_a_callable_string_should_be_used_as_string()
    {
        $query = $this->query()->join('other', 'key', '=', new Attribute('foo'));

        $this->assertEquals(
            'SELECT * FROM test_ INNER JOIN other ON key = foo',
            $query->toSql()
        );
    }

    public function test_count_alias()
    {
        $query = $this->query()->addSelect(['count' => 'COUNT(*)']);

        $this->assertEquals(
            'SELECT COUNT(*) as count FROM test_',
            $query->toSql()
        );
    }

    public function test_whereReplace()
    {
        $query = $this->query()->whereReplace('id', 1);

        $this->assertEquals('SELECT * FROM test_ WHERE id = 1', $query->toRawSql());
        $this->assertEquals('SELECT * FROM test_ WHERE id = 3', $query->whereReplace('id', 3)->toRawSql());
        $this->assertEquals('SELECT * FROM test_ WHERE id = 3 AND id < 42', $query->whereReplace('id', '<', 42)->toRawSql());
        $this->assertEquals('SELECT * FROM test_ WHERE id = 3 AND id < 42 AND raw clause', $query->whereRaw('raw clause')->toRawSql());
    }

    public function test_whereReplace_null()
    {
        $query = $this->query()->whereReplace('id', null);

        $this->assertEquals('SELECT * FROM test_ WHERE id IS NULL', $query->toRawSql());
        $this->assertEquals('SELECT * FROM test_ WHERE id = 42', $query->whereReplace('id', 42)->toRawSql());
    }

    /**
     *
     */
    public function test_inRows_with_expression()
    {
        $this->push([
            'id'   => 1,
            'name' => 'jean'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'george'
        ]);
        $this->push([
            'id'   => 3,
            'name' => 'robert'
        ]);

        $query = $this->query();
        $result = $query->inRows(new Attribute('name', 'soundex(%s)'));

        $this->assertSame(['J500', 'G620', 'R163'], $result);
    }

    /**
     *
     */
    public function test_inRow_with_expression()
    {
        $this->push([
            'id'   => 1,
            'name' => 'jean'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'george'
        ]);
        $this->push([
            'id'   => 3,
            'name' => 'robert'
        ]);

        $query = $this->query();
        $result = $query->where('id', 2)->inRow(new Attribute('name', 'soundex(%s)'));

        $this->assertSame('G620', $result);
    }

    public function test_where_with_expression_on_left()
    {
        $this->push([
            'id'   => 1,
            'name' => 'jean'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'george'
        ]);
        $this->push([
            'id'   => 3,
            'name' => 'robert'
        ]);

        $query = $this->query();
        $result = $query->where(new Attribute('name', 'soundex(%s)'), soundex('robert'))->all();

        $this->assertSame([[
            'id' => 3,
            'name' => 'robert',
            'date_insert' => null,
        ]], $result);
    }

    public function test_orWhere_with_expression_on_left()
    {
        $this->push([
            'id'   => 1,
            'name' => 'jean'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'george'
        ]);
        $this->push([
            'id'   => 3,
            'name' => 'robert'
        ]);

        $query = $this->query();
        $result = $query->where('id', 1)->orWhere(new Attribute('name', 'soundex(%s)'), soundex('robert'))->all();

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'jean',
                'date_insert' => null,
            ],
            [
                'id' => 3,
                'name' => 'robert',
                'date_insert' => null,
            ],
        ], $result);
    }

    public function test_whereNull_with_expression()
    {
        $this->push([
            'id'   => 1,
            'name' => 'jean'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'george'
        ]);
        $this->push([
            'id'   => 3,
            'name' => 'robert'
        ]);

        $query = $this->query();
        $result = $query->whereNull(new Attribute('name', 'nullif(%s, "robert")'))->all();

        $this->assertSame([
            [
                'id' => 3,
                'name' => 'robert',
                'date_insert' => null,
            ],
        ], $result);
    }

    public function test_whereNotNull_with_expression()
    {
        $this->push([
            'id'   => 1,
            'name' => 'jean'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'george'
        ]);
        $this->push([
            'id'   => 3,
            'name' => 'robert'
        ]);

        $query = $this->query();
        $result = $query->whereNotNull(new Attribute('name', 'nullif(%s, "robert")'))->all();

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'jean',
                'date_insert' => null,
            ],
            [
                'id' => 2,
                'name' => 'george',
                'date_insert' => null,
            ],
        ], $result);
    }

    public function test_orWhereNull_with_expression()
    {
        $this->push([
            'id'   => 1,
            'name' => 'jean'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'george'
        ]);
        $this->push([
            'id'   => 3,
            'name' => 'robert'
        ]);

        $query = $this->query();
        $result = $query->where('id', 1)->orWhereNull(new Attribute('name', 'nullif(%s, "robert")'))->all();

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'jean',
                'date_insert' => null,
            ],
            [
                'id' => 3,
                'name' => 'robert',
                'date_insert' => null,
            ],
        ], $result);
    }

    public function test_orWhereNotNull_with_expression()
    {
        $this->push([
            'id'   => 1,
            'name' => 'jean'
        ]);
        $this->push([
            'id'   => 2,
            'name' => 'george'
        ]);
        $this->push([
            'id'   => 3,
            'name' => 'robert'
        ]);

        $query = $this->query();
        $result = $query->where('id', 3)->orWhereNotNull(new Attribute('name', 'nullif(%s, "robert")'))->all();

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'jean',
                'date_insert' => null,
            ],
            [
                'id' => 2,
                'name' => 'george',
                'date_insert' => null,
            ],
            [
                'id' => 3,
                'name' => 'robert',
                'date_insert' => null,
            ],
        ], $result);
    }
}
