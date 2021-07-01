<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Customer;
use Bdf\Prime\Document;
use Bdf\Prime\Exception\QueryException;
use Bdf\Prime\Faction;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\CacheStatement;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\Preprocessor\DefaultPreprocessor;
use Bdf\Prime\Query\Expression\Value;
use Bdf\Prime\Query\Query;
use Bdf\Prime\Right;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SqlCompilerTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var SqlCompiler
     */
    protected $compiler;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->compiler = new SqlCompiler(Prime::connection('test'));
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
    public function test_quote()
    {
        $this->assertEquals("'12'", $this->compiler->quote(12), 'quote integer');
        $this->assertEquals("'1'", $this->compiler->quote(true), 'quote boolean');
        $this->assertEquals("'2016-01-12 14:59:40'", $this->compiler->quote(new \DateTime('2016-01-12 14:59:40')), 'quote datetime');
        $this->assertEquals("'test'", $this->compiler->quote('test'), 'quote string');
        $this->assertEquals("''", $this->compiler->quote(null), 'quote null');
        //$this->assertEquals("'first,second'", $this->compiler->quote(['first', 'second']), 'quote simple array'); //Simple array is a facade type, not handled by compiler
        $this->assertEquals("'a:2:{s:4:\"key1\";s:5:\"first\";s:4:\"key2\";s:6:\"second\";}'", $this->compiler->quote(['key1' => 'first', 'key2' => 'second']), 'quote array');
        $this->assertEquals("'O:8:\"stdClass\":1:{s:4:\"name\";s:4:\"test\";}'", $this->compiler->quote((object)['name' => 'test']), 'quote object');
    }

    /**
     *
     */
    public function test_quoteIdentifier()
    {
        $query = new CompilableClause(new DefaultPreprocessor());
        $this->assertEquals('name', $this->compiler->quoteIdentifier($query, 'name'), 'without quoteIdentifier');

        $query->useQuoteIdentifier();
        $this->assertEquals('"name"', $this->compiler->quoteIdentifier($query, 'name'), 'with quoteIdentifier');
    }

    /**
     *
     */
    public function test_quoteIdentifiers()
    {
        $query = new CompilableClause(new DefaultPreprocessor());
        $this->assertEquals(['id', 'name'], $this->compiler->quoteIdentifiers($query, ['id', 'name']), 'without quoteIdentifier');

        $query->useQuoteIdentifier();
        $this->assertEquals(['"id"', '"name"'], $this->compiler->quoteIdentifiers($query, ['id', 'name']), 'with quoteIdentifier');
    }

    /**
     *
     */
    public function test_compiler_will_not_modify_query_on_insert()
    {
        /** @var Query $query */
        $query = User::builder();

        $query
            ->setValue('name', 'John')
            ->setValue('id', '123')
        ;

        $bck = clone $query;

        $this->compiler->compileInsert($query);

        $this->assertEquals($bck, $query);
    }

    /**
     *
     */
    public function test_compiler_will_not_modify_query_on_update()
    {
        /** @var Query $query */
        $query = Faction::builder();

        $query
            ->set('name', 'Orc')
            ->set('domain', 'admin')
            ->where('id', '123')
        ;

        $bck = clone $query;

        $this->compiler->compileUpdate($query);

        $this->assertEquals($bck, $query);
    }

    /**
     *
     */
    public function test_compiler_will_not_modify_query_on_delete()
    {
        /** @var Query $query */
        $query = Faction::builder();

        $query->where('id', '123');

        $bck = clone $query;

        $this->compiler->compileDelete($query);

        $this->assertEquals($bck, $query);
    }

    /**
     *
     */
    public function test_compiler_will_not_modify_query_on_select_simple()
    {
        /** @var Query $query */
        $query = Faction::builder();

        $query->where('id', '123');

        $bck = clone $query;

        $this->compiler->compileSelect($query);

        $this->assertEquals($bck, $query);
    }

    /**
     *
     */
    public function test_compiler_will_not_modify_query_on_select_with_join()
    {
        /** @var Query $query */
        $query = User::builder();

        $query->where('faction.domain', 'orc');

        $bck = clone $query;

        $this->compiler->compileSelect($query);

        $this->assertEquals($bck, $query);
    }

    /**
     *
     */
    public function test_compiler_will_not_modify_query_on_select_with_table()
    {
        /** @var Query $query */
        $query = User::builder();

        $query->from(Faction::class, 'f')
            ->where('f.id', '123')
        ;

        $bck = clone $query;

        $this->compiler->compileSelect($query);

        $this->assertEquals($bck, $query);
    }

    /**
     *
     */
    public function test_compile_twice_unmodified_query()
    {
        $methods = [
            'compileColumns',
            'compileFrom',
            'compileGroup',
            'compileHaving',
            'compileOrder',
            'compileWhere',
            'compileJoins',
        ];

        /** @var SqlCompiler|\PHPUnit_Framework_MockObject_MockObject $compiler */
        $compiler = $this->getMockBuilder(SqlCompiler::class)
            ->enableOriginalConstructor()
            ->enableOriginalClone()
            ->setConstructorArgs([User::repository()->connection()])
            ->enableProxyingToOriginalMethods()
            ->disallowMockingUnknownTypes()
            ->setMethods($methods)
            ->getMock()
        ;

        /** @var Query $query */
        $query = User::builder();
        $query->setCompiler($compiler);


        $query->where('faction.domain', 'orc');

        // Methods are called once
        foreach ($methods as $method) {
            $compiler->expects($this->once())
                ->method($method);
        }

        $sql = $compiler->compileSelect($query);

        $this->assertEquals('SELECT t0.* FROM user_ t0 INNER JOIN faction_ t1 ON t1.id_ = t0.faction_id WHERE t1.domain_ = ? AND (t1.enabled_ = ?) AND (t1.domain_ = ?)', $sql);
        $this->assertSame($sql, $compiler->compileSelect($query));
    }

    /**
     *
     */
    public function test_compiler_reset_will_recompile_part()
    {
        $methods = [
            'compileColumns',
            'compileFrom',
            'compileGroup',
            'compileHaving',
            'compileOrder',
            'compileWhere',
            'compileJoins',
        ];

        /** @var SqlCompiler|\PHPUnit_Framework_MockObject_MockObject $compiler */
        $compiler = $this->getMockBuilder(SqlCompiler::class)
            ->enableOriginalConstructor()
            ->enableOriginalClone()
            ->setConstructorArgs([User::repository()->connection()])
            ->enableProxyingToOriginalMethods()
            ->disallowMockingUnknownTypes()
            ->setMethods($methods)
            ->getMock()
        ;

        /** @var Query $query */
        $query = User::builder();
        $query->setCompiler($compiler);

        $query->where('faction.domain', 'orc');

        // Methods are called once
        foreach ($methods as $method) {
            if ($method !== 'compileFrom') {
                $compiler->expects($this->once())
                    ->method($method);
            }
        }

        // From is called twice
        $compiler->expects($this->exactly(2))
            ->method('compileFrom');

        $sql = $compiler->compileSelect($query);
        $query->state()->invalidate('from');

        $this->assertSame($sql, $compiler->compileSelect($query));
    }

    /**
     *
     */
    public function test_compile_change_pagination_will_no_recompile()
    {
        $methods = [
            'compileColumns',
            'compileFrom',
            'compileGroup',
            'compileHaving',
            'compileOrder',
            'compileWhere',
            'compileJoins',
        ];

        /** @var SqlCompiler|\PHPUnit_Framework_MockObject_MockObject $compiler */
        $compiler = $this->getMockBuilder(SqlCompiler::class)
            ->enableOriginalConstructor()
            ->enableOriginalClone()
            ->setConstructorArgs([User::repository()->connection()])
            ->enableProxyingToOriginalMethods()
            ->disallowMockingUnknownTypes()
            ->setMethods($methods)
            ->getMock()
        ;

        /** @var Query $query */
        $query = User::builder();
        $query->setCompiler($compiler);

        $query->where('faction.domain', 'orc')->limit(10, 0);

        // Methods are called once
        foreach ($methods as $method) {
            $compiler->expects($this->once())
                ->method($method);
        }

        $baseSql = 'SELECT t0.* FROM user_ t0 INNER JOIN faction_ t1 ON t1.id_ = t0.faction_id WHERE t1.domain_ = ? AND (t1.enabled_ = ?) AND (t1.domain_ = ?)';

        $query->limit(10, 1);
        $sql = $compiler->compileSelect($query);
        $this->assertEquals($baseSql . ' LIMIT 10 OFFSET 1', $sql);

        $query->limit(10, 10);
        $sql = $compiler->compileSelect($query);
        $this->assertEquals($baseSql . ' LIMIT 10 OFFSET 10', $sql);
    }

    /**
     *
     */
    public function test_compiler_reset_functional()
    {
        /** @var Query $query */
        $query = User::builder();
        $query->setCompiler($this->compiler);

        $this->assertEquals('SELECT t0.* FROM user_ t0', $this->compiler->compileSelect($query));

        $query->where('name', 'John');

        $this->assertEquals('SELECT t0.* FROM user_ t0 WHERE t0.name_ = ?', $this->compiler->compileSelect($query));

        $query->from(Document::class, 'd');

        $this->assertEquals('SELECT t0.* FROM user_ t0, document_ d WHERE t0.name_ = ?', $this->compiler->compileSelect($query));

        $query->where('customer.name', 'aa');
        $query->state()->invalidate('joins'); //Remove when fix WHERE-JOIN reset

        $this->assertEquals('SELECT t0.* FROM user_ t0, document_ d INNER JOIN customer_ t1 ON t1.id_ = t0.customer_id WHERE t0.name_ = ? AND t1.name_ = ?', $this->compiler->compileSelect($query));
    }

    /**
     *
     */
    public function test_compiler_useQuoteIdentifier_on_insert()
    {
        /** @var Query $query */
        $query = Right::builder();
        $query->setCompiler($this->compiler);

        $query
            ->setValue('id', 1)
            ->setValue('userId', 25)
            ->setValue('name', 'admin')
        ;

        $this->assertEquals('INSERT INTO "rights_" ("id_", "user_id", "name_") VALUES(?, ?, ?)', $this->compiler->compileInsert($query));
    }

    /**
     *
     */
    public function test_compiler_useQuoteIdentifier_on_update()
    {
        /** @var Query $query */
        $query = Right::builder();
        $query->setCompiler($this->compiler);

        $query
            ->where('id', 1)
            ->set('name', 'admin')
        ;

        $this->assertEquals('UPDATE "rights_" SET "name_" = ? WHERE "id_" = ?', $this->compiler->compileUpdate($query));
    }

    /**
     *
     */
    public function test_compiler_useQuoteIdentifier_on_delete()
    {
        /** @var Query $query */
        $query = Right::builder();
        $query->setCompiler($this->compiler);

        $query->where('id', 1);

        $this->assertEquals('DELETE FROM "rights_" WHERE "id_" = ?', $this->compiler->compileDelete($query));
    }

    /**
     *
     */
    public function test_where_nested_with_constraint_relation()
    {
        $query = User::where([
            'roles'        => new Value([1, 3]),
            'faction.name' => 'my faction',
        ]);

        $this->assertEquals('SELECT t0.* FROM user_ t0 INNER JOIN faction_ t1 ON t1.id_ = t0.faction_id WHERE (t0.roles_ = ? AND t1.name_ = ?) AND (t1.enabled_ = ?) AND (t1.domain_ = ?)', $query->toSql());
    }

    /**
     *
     */
    public function test_compileSelect_with_complexe_aggregate()
    {
        $query = Customer::where('users.name', ['Shrek', 'Mickey', 'Donald'])->distinct();
        $query->statements['aggregate'] = ['count', '*'];

        $this->assertEquals(
            'SELECT COUNT(*) AS aggregate FROM (SELECT DISTINCT t0.* FROM customer_ t0 INNER JOIN user_ t1 ON t1.customer_id = t0.id_ WHERE t1.name_ IN (?,?,?)) as derived_query',
            $this->compiler->compileSelect($query)
        );
    }

    /**
     *
     */
    public function test_compileInsert_with_insert_select_query()
    {
        $query = Customer::where('users.name', ['Shrek', 'Mickey', 'Donald']);
        $insert = Customer::values($query);

        $this->assertEquals(
            'INSERT INTO customer_ SELECT t0.* FROM customer_ t0 INNER JOIN user_ t1 ON t1.customer_id = t0.id_ WHERE t1.name_ IN (?,?,?)',
            $this->compiler->compileInsert($insert)
        );
    }

    /**
     *
     */
    public function test_compileInsert_with_insert_select_query_with_columns()
    {
        $query = Customer::where('users.name', ['Shrek', 'Mickey', 'Donald'])->select(['name', 'parentId' => 'id']);
        $insert = Customer::values($query);

        $this->assertEquals(
            'INSERT INTO customer_ (name_, parent_id) SELECT t0.name_ as name_, t0.id_ as parent_id FROM customer_ t0 INNER JOIN user_ t1 ON t1.customer_id = t0.id_ WHERE t1.name_ IN (?,?,?)',
            $this->compiler->compileInsert($insert)
        );
    }

    /**
     *
     */
    public function test_sub_query_in_from_clause()
    {
        $subQuery = User::repository()->select(['name', 'customer.id']);

        $query = User::repository()->connection()
            ->from($subQuery, 'alias')
            ->select('name_')
            ->group('customer_id');

        $this->assertEquals('SELECT name_ FROM (SELECT t0.name_, t0.customer_id FROM user_ t0) as alias GROUP BY customer_id', $query->toSql());
    }

    /**
     *
     */
    public function test_subquery_x_db()
    {
        Prime::service()->connections()->declareConnection('mysql', ['adapter' => 'mysql', 'serverVersion' => '5.6']);
        $mysql = Prime::service()->connections()->getConnection('mysql');

        $connection = $this->getMockBuilder(SimpleConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['executeUpdate', 'executeQuery', 'getDatabasePlatform', 'getDatabase', 'platform', 'factory'])
            ->getMock();
        $connection->expects($this->any())->method('getDatabasePlatform')->willReturn($mysql->getDatabasePlatform());
        $connection->expects($this->any())->method('getDatabase')->willReturn('TEST');
        $connection->expects($this->any())->method('platform')->willReturn($mysql->platform());
        $connection->expects($this->any())->method('factory')->willReturn($mysql->factory());

        $subConnection = $this->getMockBuilder(SimpleConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['executeUpdate', 'executeQuery', 'getDatabasePlatform', 'getDatabase', 'platform', 'factory'])
            ->getMock();
        $subConnection->expects($this->any())->method('getDatabasePlatform')->willReturn($mysql->getDatabasePlatform());
        $subConnection->expects($this->any())->method('getDatabase')->willReturn('TEST2');
        $subConnection->expects($this->any())->method('platform')->willReturn($mysql->platform());
        $subConnection->expects($this->any())->method('factory')->willReturn($mysql->factory());

        $subQuery = new Query($subConnection);
        $subQuery
            ->select(['name_', 'customer_id'])
            ->from('user_');

        $query = new Query($connection);
        $query
            ->select('name_')
            ->from($subQuery, 'alias')
            ->group('customer_id');

        $compiler = new SqlCompiler($connection);

        $this->assertEquals('SELECT name_ FROM (SELECT name_, customer_id FROM TEST2.user_) as alias GROUP BY customer_id', $compiler->compileSelect($query));

        Prime::service()->connections()->removeConnection('mysql');
    }

    /**
     *
     */
    public function test_insert_select_x_db()
    {
        Prime::service()->connections()->declareConnection('mysql', ['adapter' => 'mysql', 'serverVersion' => '5.6']);
        $mysql = Prime::service()->connections()->getConnection('mysql');

        $connection = $this->getMockBuilder(SimpleConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['executeUpdate', 'executeQuery', 'getDatabasePlatform', 'getDatabase', 'platform', 'factory'])
            ->getMock();
        $connection->expects($this->any())->method('getDatabasePlatform')->willReturn($mysql->getDatabasePlatform());
        $connection->expects($this->any())->method('getDatabase')->willReturn('TEST');
        $connection->expects($this->any())->method('platform')->willReturn($mysql->platform());
        $connection->expects($this->any())->method('factory')->willReturn($mysql->factory());

        $subConnection = $this->getMockBuilder(SimpleConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['executeUpdate', 'executeQuery', 'getDatabasePlatform', 'getDatabase', 'platform', 'factory'])
            ->getMock();
        $subConnection->expects($this->any())->method('getDatabasePlatform')->willReturn($mysql->getDatabasePlatform());
        $subConnection->expects($this->any())->method('getDatabase')->willReturn('TEST2');
        $subConnection->expects($this->any())->method('platform')->willReturn($mysql->platform());
        $subConnection->expects($this->any())->method('factory')->willReturn($mysql->factory());

        $query = Customer::where('users.name', ['Shrek', 'Mickey', 'Donald']);
        $query->on($subConnection);

        $insert = Customer::values($query);
        $compiler = new SqlCompiler($connection);

        $this->assertEquals(
            'INSERT INTO customer_ SELECT t0.* FROM TEST2.customer_ t0 INNER JOIN TEST2.user_ t1 ON t1.customer_id = t0.id_ WHERE t1.name_ IN (?,?,?)',
            $compiler->compileInsert($insert)
        );

        Prime::service()->connections()->removeConnection('mysql');
    }

    /**
     *
     */
    public function test_compile_insert_missing_table()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('The insert table name is missing');

        $query = new Query(Prime::connection('test'));
        $query->values(['foo' => 'bar']);
        $this->compiler->compileInsert($query);
    }

    /**
     *
     */
    public function test_compile_update_missing_table()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('The update table name is missing');

        $query = new Query(Prime::connection('test'));
        $query->values(['foo' => 'bar']);
        $this->compiler->compileUpdate($query);
    }

    /**
     *
     */
    public function test_compile_delete_missing_table()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('The delete table name is missing');

        $query = new Query(Prime::connection('test'));
        $this->compiler->compileDelete($query);
    }
}
