<?php

namespace Bdf\Prime\Query\Custom\KeyValue;

use Bdf\Prime\Cache\ArrayCache;
use Bdf\Prime\Cache\CacheKey;
use Bdf\Prime\Cache\DoctrineCacheAdapter;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Customer;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Compiler\Preprocessor\OrmPreprocessor;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\QueryRepositoryExtension;
use Bdf\Prime\TestEntity;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 * Class KeyValueQueryTest
 */
class KeyValueQueryTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var SimpleConnection
     */
    private $connection;


    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->connection = Prime::connection('test');
    }

    protected function tearDown(): void
    {
        $this->primeStop();
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([TestEntity::class, User::class]);
    }

    /**
     * @param PreprocessorInterface $preprocessor
     *
     * @return KeyValueQuery
     */
    public function query($preprocessor = null)
    {
        $query = new KeyValueQuery($this->connection, $preprocessor);
        $query->setCompiler(new KeyValueSqlCompiler($this->connection));

        return $query;
    }

    /**
     *
     */
    public function test_compile_will_save_compiled_statement()
    {
        $query = $this->query()->from('test_')->where(['id' => 5]);

        $this->assertEquals($this->connection->prepare('SELECT * FROM test_ WHERE id = ?'), $query->compile());
        $this->assertSame($query->compile(), $query->compile());
    }

    /**
     *
     */
    public function test_select_all()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->assertEquals([
            [
                'id' => 1,
                'name' => 'John'
            ],
            [
                'id' => 2,
                'name' => 'Mickey'
            ]
        ], $this->query()->from('test_')->project(['id', 'name'])->all());
    }

    /**
     *
     */
    public function test_change_from()
    {
        $query = $this->query()->from('user_');

        $this->assertEquals($this->connection->prepare('SELECT * FROM user_'), $query->compile());
        $this->assertEquals($this->connection->prepare('SELECT * FROM test_'), $query->from('test_')->compile());
    }

    /**
     *
     */
    public function test_select_where_single_condition()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->assertEquals([
            [
                'id' => 2,
                'name' => 'Mickey'
            ]
        ], $this->query()->from('test_')->project(['id', 'name'])->where('id', 2)->all());
    }

    /**
     *
     */
    public function test_select_where_multiple_conditions()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->assertEquals([
            [
                'id' => 2,
                'name' => 'Mickey'
            ]
        ], $this->query()->from('test_')->project(['id', 'name'])->where(['id' => 2, 'name' => 'Mickey'])->all());
    }

    /**
     *
     */
    public function test_aggregate()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->assertEquals(1, $this->query()->from('test_')->min('id'));
        $this->assertEquals(2, $this->query()->from('test_')->max('id'));
        $this->assertEquals(3, $this->query()->from('test_')->sum('id'));
        $this->assertEquals(1.5, $this->query()->from('test_')->avg('id'));
        $this->assertEquals(2, $this->query()->from('test_')->count());
    }

    /**
     *
     */
    public function test_paginationCount()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->assertEquals(2, $this->query()->from('test_')->paginationCount());

        $query = $this->query()->from('test_')->limit(1)->offset(1);
        $this->assertEquals(2, $query->paginationCount());
        $this->assertEquals(1, $query->getLimit());
        $this->assertEquals(1, $query->getOffset());
    }

    /**
     *
     */
    public function test_select_should_not_be_recompiled_when_changing_bindings()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $query = $this->query()->from('test_')->project(['id', 'name'])->where('id', 1);

        $compiled = $query->compile();

        $this->assertSame([1], $query->getBindings());
        $this->assertEquals([
            [
                'id' => 1,
                'name' => 'John'
            ]
        ], $query->all());

        $query->where('id', 2);

        $this->assertEquals([
            [
                'id' => 2,
                'name' => 'Mickey'
            ]
        ], $query->all());

        $this->assertSame([2], $query->getBindings());
        $this->assertSame($compiled, $query->compile());
    }

    /**
     *
     */
    public function test_select_should_be_recompiled_when_changing_condition()
    {
        $query = $this->query()->from('test_')->project(['id', 'name'])->where('id', 1);

        $compiled = $query->compile();

        $query->where('name', 'John');

        $this->assertNotSame($compiled, $query->compile());
        $this->assertSame([1, 'John'], $query->getBindings());
    }

    /**
     *
     */
    public function test_select_should_not_be_recompiled_multi_conditions()
    {
        $query = $this->query()->from('test_')->project(['id', 'name'])->where(['id' => 1, 'name' => 'John']);

        $compiled = $query->compile();
        $this->assertSame([1, 'John'], $query->getBindings());

        $query->where(['id' => 2, 'name' => 'Mickey']);

        $this->assertSame($compiled, $query->compile());
        $this->assertSame([2, 'Mickey'], $query->getBindings());
    }

    /**
     *
     */
    public function test_select_should_be_recompiled_multi_conditions()
    {
        $query = $this->query()->from('test_')->project(['id', 'name'])->where(['id' => 1, 'name' => 'John']);

        $compiled = $query->compile();

        $query->where(['id' => 2, 'name' => 'Mickey', 'date_insert' => new \DateTime('2018-03-12 15:25:00')]);

        $this->assertNotSame($compiled, $query->compile());
        $this->assertSame([2, 'Mickey', '2018-03-12 15:25:00'], $query->getBindings());
    }

    /**
     *
     */
    public function test_select_change_pagination_will_not_recompile_query()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->connection->insert('test_', [
            'id' => 3,
            'name' => 'Donald'
        ]);


        $query = $this->query()->from('test_')->limitPage(1, 2)->project(['name']);

        $compiled = $query->compile();
        $this->assertEquals('SELECT name FROM test_ LIMIT ? OFFSET ?', $query->toSql());
        $this->assertEquals([['name' => 'John'], ['name' => 'Mickey']], $query->all());

        $query->limitPage(2, 2);
        $this->assertSame($compiled, $query->compile());
        $this->assertEquals([['name' => 'Donald']], $query->all());
    }

    /**
     *
     */
    public function test_select_simple_limit()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->connection->insert('test_', [
            'id' => 3,
            'name' => 'Donald'
        ]);


        $query = $this->query()->from('test_')->project(['name'])->limit(1);
        $compiled = $query->compile();

        $this->assertEquals('SELECT name FROM test_ LIMIT 1', $query->toSql());
        $this->assertEquals([['name' => 'John']], $query->all());

        $query->limit(1);
        $this->assertSame($compiled, $query->compile());

        $query->limit(2);
        $this->assertNotSame($compiled, $query->compile());
        $this->assertEquals('SELECT name FROM test_ LIMIT 2', $query->toSql());
        $this->assertEquals([['name' => 'John'], ['name' => 'Mickey']], $query->all());
    }

    /**
     *
     */
    public function test_select_simple_offset()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->connection->insert('test_', [
            'id' => 3,
            'name' => 'Donald'
        ]);


        $query = $this->query()->from('test_')->project(['name'])->offset(2);

        $this->assertEquals('SELECT name FROM test_ LIMIT -1 OFFSET 2', $query->toSql());
        $this->assertEquals([['name' => 'Donald']], $query->all());
    }

    /**
     *
     */
    public function test_walk()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->connection->insert('test_', [
            'id' => 3,
            'name' => 'Donald'
        ]);

        $query = $this->query()->from('test_')->project(['name']);

        $this->assertEquals(3, $query->walk(1)->size());
        $this->assertEquals(3, $query->walk(100)->size());
        $this->assertEquals([['name' => 'John'], ['name' => 'Mickey'], ['name' => 'Donald']], iterator_to_array($query->walk(1)));
        $this->assertEquals([['name' => 'John'], ['name' => 'Mickey'], ['name' => 'Donald']], iterator_to_array($query));
    }

    /**
     *
     */
    public function test_execute_with_columns()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->assertEquals([
            [
                'id' => 1,
                'name' => 'John'
            ],
            [
                'id' => 2,
                'name' => 'Mickey'
            ]
        ], $this->query()->from('test_')->execute(['id', 'name']));
    }

    /**
     *
     */
    public function test_delete_all()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->assertEquals(2, $this->query()->from('test_')->delete());
        $this->assertEmpty($this->connection->from('test_')->all());
    }

    /**
     *
     */
    public function test_delete_condition()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->assertEquals(1, $this->query()->from('test_')->where('id', 2)->delete());
        $this->assertEquals([[
            'id' => 1,
            'name' => 'John'
        ]], $this->connection->from('test_')->select(['id', 'name'])->all());
    }

    /**
     *
     */
    public function test_select_delete_will_recompile()
    {
        $query = $this->query()->from('test_');

        $query->execute();
        $this->assertEquals($this->connection->prepare('SELECT * FROM test_'), $query->compile());

        $query->delete();
        $this->assertEquals($this->connection->prepare('DELETE FROM test_'), $query->compile());

        $query->execute();
        $this->assertEquals($this->connection->prepare('SELECT * FROM test_'), $query->compile());
    }

    /**
     *
     */
    public function test_dbal_error()
    {
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('dbal internal error has occurred');

        $this->query()->from('not_found')->execute();
    }

    /**
     *
     */
    public function test_dbal_error_delete()
    {
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('dbal internal error has occurred');

        $this->query()->from('not_found')->delete();
    }

    /**
     *
     */
    public function test_dbal_error_update()
    {
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('dbal internal error has occurred');

        $this->query()->from('not_found')->update();
    }

    /**
     *
     */
    public function test_orm_select()
    {
        $this->pack()->nonPersist([
            $user = new User([
                'id'       => 1,
                'name'     => 'john',
                'customer' => new Customer(['id' => 1]),
                'roles'    => [1, 4],
            ])
        ]);

        $query = $this->query(new OrmPreprocessor(User::repository()))->from('user_')->where('id', 1);
        (new QueryRepositoryExtension(User::repository()))->apply($query);

        $this->assertEquals([$user], $query->all());
        $this->assertEquals($user, $query->get(1));
    }

    /**
     *
     */
    public function test_select_will_fill_cache()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $expected = [[
            'id' => 1,
            'name' => 'John'
        ]];

        $cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ArrayCache());

        $query = $this->query()->from('test_');
        $query
            ->setCache($cache)
            ->useCache()
        ;

        $this->assertEquals($expected, $query->execute(['id', 'name']));
        $this->assertEquals($expected, $cache->get(new CacheKey('test:test_', sha1('SELECT id, name FROM test_-a:0:{}'))));
    }

    /**
     *
     */
    public function test_select_cached()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $expected = [[
            'id' => 1,
            'name' => 'John'
        ]];

        $cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ArrayCache());

        $query = $this->query()->from('test_');
        $query->setCache($cache)->useCache();
        $this->assertEquals($expected, $query->execute(['id', 'name']));

        // insert without clear cache
        $this->connection->insert('test_', [
            'id' => 2,
            'name' => 'Mickey'
        ]);

        $this->assertEquals($expected, $query->execute(['id', 'name']));
    }

    /**
     *
     */
    public function test_delete_will_clear_cache()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $expected = [[
            'id' => 1,
            'name' => 'John'
        ]];

        $cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ArrayCache());

        $query = $this->query()->from('test_')->where('id', 1);
        $query->setCache($cache);
        $this->assertEquals($expected, $query->execute(['id', 'name']));

        $query->delete();

        $this->assertNull($cache->get(new CacheKey('test:test_', sha1('SELECT id, name FROM test_-a:0:{}'))));

        $this->assertEmpty($query->execute(['id', 'name']));
    }

    /**
     *
     */
    public function test_schema_changed()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $query = $this->query()->from('test_')->where('id', 1);
        $this->assertEquals([[
            'id'          => 1,
            'name'        => 'John'
        ]], $query->execute(['id', 'name']));

        // Change the schema
        TestEntity::repository()->schema()->drop();
        TestEntity::repository()->schema()->migrate();

        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $this->assertEquals([[
            'id'          => 1,
            'name'        => 'John',
        ]], $query->execute(['id', 'name']));
    }

    /**
     *
     */
    public function test_update()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $query = $this->query()->from('test_')->where('id', 1)->values(['name' => 'Bob']);

        $this->assertEquals(1, $query->update());
        $this->assertEquals('Bob', $this->query()->from('test_')->where('id', 1)->inRow('name'));

        $query->update(['name' => 'Bill']);
        $this->assertEquals('Bill', $this->query()->from('test_')->where('id', 1)->inRow('name'));
    }

    /**
     *
     */
    public function test_update_change_values_on_same_columns_will_not_recompile()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $query = $this->query()->from('test_')->where('id', 1)->values(['name' => 'Bob']);

        $query->update();
        $compiled = $query->compile();

        $query->values(['name' => 'Robert']);
        $this->assertEquals(['Robert', 1], $query->getBindings());
        $this->assertSame($compiled, $query->compile());

        $query->where('id', 5);
        $this->assertEquals(['Robert', 5], $query->getBindings());
        $this->assertSame($compiled, $query->compile());
    }

    /**
     *
     */
    public function test_update_new_column_will_recompile()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $query = $this->query()->from('test_')->where('id', 1)->values(['name' => 'Bob']);

        $query->update();
        $compiled = $query->compile();

        $query->values(['foreign_key' => 2]);
        $this->assertEquals([2, 1], $query->getBindings());
        $this->assertNotSame($compiled, $query->compile());
    }

    /**
     *
     */
    public function test_update_will_clear_cache()
    {
        $this->connection->insert('test_', [
            'id' => 1,
            'name' => 'John'
        ]);

        $expected = [[
            'id' => 1,
            'name' => 'John'
        ]];

        $cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ArrayCache());

        $query = $this->query()->from('test_')->where('id', 1);
        $query->setCache($cache);
        $this->assertEquals($expected, $query->execute(['id', 'name']));

        $query->update(['name' => 'Bob']);

        $this->assertNull($cache->get(new CacheKey('test:test_', sha1('SELECT id, name FROM test_-a:0:{}'))));

        $this->assertEquals([[
            'id' => 1,
            'name' => 'Bob'
        ]], $query->execute(['id', 'name']));
    }
}
