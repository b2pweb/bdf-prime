<?php

namespace Bdf\Prime\Query\Custom\KeyValue;

use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Compiler\Preprocessor\OrmPreprocessor;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Expression\Raw;
use Bdf\Prime\TestEntity;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

class KeyValueSqlCompilerTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var KeyValueSqlCompiler
     */
    private $compiler;

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
        $this->compiler = new KeyValueSqlCompiler($this->connection);
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([TestEntity::class, User::class]);
    }

    /**
     * @return KeyValueQuery
     */
    public function query()
    {
        return new KeyValueQuery($this->connection);
    }

    /**
     *
     */
    public function test_compileSelect_default()
    {
        $query = $this->query()->from('test_');

        $this->assertEquals($this->connection->prepare('SELECT * FROM test_'), $this->compiler->compileSelect($query));
        $this->assertSame([], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compile_twice_will_return_same_statement_default()
    {
        $query = $this->query()->from('test_');

        $this->assertSame($this->compiler->compileSelect($query), $this->compiler->compileSelect($query));
    }

    /**
     *
     */
    public function test_compileSelect_where()
    {
        $query = $this->query()->from('test_')->where(['id' => 5, 'name' => 'John']);

        $this->assertEquals($this->connection->prepare('SELECT * FROM test_ WHERE id = ? AND name = ?'), $this->compiler->compileSelect($query));
        $this->assertSame([5, 'John'], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compileSelect_quote_identifier()
    {
        $query = $this->query()->from('test_')->where(['id' => 5, 'name' => 'John']);
        $query->useQuoteIdentifier();

        $this->assertEquals($this->connection->prepare('SELECT * FROM "test_" WHERE "id" = ? AND "name" = ?'), $this->compiler->compileSelect($query));
        $this->assertSame([5, 'John'], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compileSelect_projection()
    {
        $query = $this->query()->from('test_')->project(['id', 'name']);

        $this->assertEquals($this->connection->prepare('SELECT id, name FROM test_'), $this->compiler->compileSelect($query));
    }

    /**
     *
     */
    public function test_compileSelect_projection_alias()
    {
        $query = $this->query()->from('test_')->project(['first_name' => 'name']);

        $this->assertEquals($this->connection->prepare('SELECT name as first_name FROM test_'), $this->compiler->compileSelect($query));
    }

    /**
     *
     */
    public function test_compileSelect_projection_expression()
    {
        $query = $this->query()->from('test_')->project(['foo' => new Raw('foreign_key || "-" || name')]);

        $this->assertEquals($this->connection->prepare('SELECT foreign_key || "-" || name as foo FROM test_'), $this->compiler->compileSelect($query));
    }

    /**
     *
     */
    public function test_compileSelect_aggregate()
    {
        $query = $this->query()->from('test_');
        $query->statements['aggregate'] = ['count', '*'];

        $this->assertEquals($this->connection->prepare('SELECT COUNT(*) AS aggregate FROM test_'), $this->compiler->compileSelect($query));
    }

    /**
     *
     */
    public function test_compileSelect_with_orm_preprocessor()
    {
        $query = (new KeyValueQuery($this->connection, new OrmPreprocessor(User::repository())))
            ->from('user_')
            ->project(['customer.id', 'faction.id'])
            ->where(['id' => 5, 'name' => 'John'])
        ;

        $this->assertEquals($this->connection->prepare('SELECT customer_id, faction_id FROM user_ WHERE id_ = ? AND name_ = ?'), $this->compiler->compileSelect($query));
        $this->assertSame(['5', 'John'], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compileSelect_limit_simple()
    {
        $query = $this->query()->from('test_')->where('id', 5)->limit(1);

        $this->assertEquals($this->connection->prepare('SELECT * FROM test_ WHERE id = ? LIMIT 1'), $this->compiler->compileSelect($query));
        $this->assertSame([5], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compileSelect_offset_simple()
    {
        $query = $this->query()->from('test_')->where('id', 5)->offset(1);

        $this->assertEquals($this->connection->prepare('SELECT * FROM test_ WHERE id = ? LIMIT -1 OFFSET 1'), $this->compiler->compileSelect($query));
        $this->assertSame([5], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compileSelect_limit_pagination()
    {
        $query = $this->query()->from('test_')->where('id', 5)->limit(10, 20);

        $this->assertEquals($this->connection->prepare('SELECT * FROM test_ WHERE id = ? LIMIT ? OFFSET ?'), $this->compiler->compileSelect($query));
        $this->assertSame([5, 10, 20], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compileDelete()
    {
        $query = $this->query()->from('test_')->where(['id' => 5]);

        $this->assertEquals($this->connection->prepare('DELETE FROM test_ WHERE id = ?'), $this->compiler->compileDelete($query));
        $this->assertSame([5], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compileUpdate()
    {
        $query = $this->query()->from('test_')->where(['id' => 5])->values(['name' => 'Robert']);

        $refl = new \ReflectionMethod($query, 'setType');
        $refl->setAccessible(true);
        $refl->invokeArgs($query, [Compilable::TYPE_UPDATE]);

        $compiled = $this->compiler->compileUpdate($query);
        $this->assertEquals($this->connection->prepare('UPDATE test_ SET name = ? WHERE id = ?'), $compiled);
        $this->assertSame(['Robert', 5], $this->compiler->getBindings($query));

        $this->assertSame($compiled, $this->compiler->compileUpdate($query));
    }

    /**
     *
     */
    public function test_compileUpdate_multiple_attributes()
    {
        $query = $this->query()->from('test_')->where(['id' => 5])->values(['name' => 'Robert', 'foreign_key' => 42]);

        $refl = new \ReflectionMethod($query, 'setType');
        $refl->setAccessible(true);
        $refl->invokeArgs($query, [Compilable::TYPE_UPDATE]);

        $compiled = $this->compiler->compileUpdate($query);
        $this->assertEquals($this->connection->prepare('UPDATE test_ SET name = ?, foreign_key = ? WHERE id = ?'), $compiled);
        $this->assertSame(['Robert', 42, 5], $this->compiler->getBindings($query));

        $this->assertSame($compiled, $this->compiler->compileUpdate($query));
    }

    /**
     *
     */
    public function test_compileUpdate_quoteIdentifier()
    {
        $query = $this->query()->from('test_')->where(['id' => 5])->values(['name' => 'Robert']);
        $query->useQuoteIdentifier();

        $this->assertEquals($this->connection->prepare('UPDATE "test_" SET "name" = ? WHERE "id" = ?'), $this->compiler->compileUpdate($query));
    }

    /**
     *
     */
    public function test_compileUpdate_without_where()
    {
        $query = $this->query()->from('test_')->values(['name' => 'Robert']);

        $refl = new \ReflectionMethod($query, 'setType');
        $refl->setAccessible(true);
        $refl->invokeArgs($query, [Compilable::TYPE_UPDATE]);

        $compiled = $this->compiler->compileUpdate($query);
        $this->assertEquals($this->connection->prepare('UPDATE test_ SET name = ?'), $compiled);
        $this->assertSame(['Robert'], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compileUpdate_explicit_type()
    {
        $query = $this->query()->from('test_')->where(['id' => 5])->values(['name' => ['1', '2']], ['name' => 'array']);

        $refl = new \ReflectionMethod($query, 'setType');
        $refl->setAccessible(true);
        $refl->invokeArgs($query, [Compilable::TYPE_UPDATE]);

        $compiled = $this->compiler->compileUpdate($query);
        $this->assertEquals($this->connection->prepare('UPDATE test_ SET name = ? WHERE id = ?'), $compiled);
        $this->assertSame([',1,2,', 5], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compileUpdate_with_orm_preprocessor()
    {
        $query = (new KeyValueQuery($this->connection, new OrmPreprocessor(User::repository())))
            ->from('user_')
            ->where(['id' => 5])
            ->values(['name' => 'Bob'])
        ;

        $refl = new \ReflectionMethod($query, 'setType');
        $refl->setAccessible(true);
        $refl->invokeArgs($query, [Compilable::TYPE_UPDATE]);

        $this->assertEquals($this->connection->prepare('UPDATE user_ SET name_ = ? WHERE id_ = ?'), $this->compiler->compileUpdate($query));
        $this->assertSame(['Bob', '5'], $this->compiler->getBindings($query));
    }
}
