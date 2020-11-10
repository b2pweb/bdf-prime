<?php

namespace Bdf\Prime\Query\Custom\BulkInsert;

use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\FooType;
use Bdf\Prime\MyCustomNullableEntity;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Compiler\Preprocessor\OrmPreprocessor;
use Bdf\Prime\Schema\Builder\TypesHelperTableBuilder;
use Bdf\Prime\TestEntity;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class BulkInsertSqlCompilerTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var BulkInsertSqlCompiler
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

        $this->compiler = new BulkInsertSqlCompiler($this->connection = $this->prime()->connection('test'));

        $this->connection->schema()
            ->table('person', function (TypesHelperTableBuilder $builder) {
                $builder->integer('id')->autoincrement();
                $builder->string('first_name');
                $builder->string('last_name');
                $builder->integer('age');
                $builder->dateTime('birthday');
            })
        ;
    }

    /**
     *
     */
    public function test_compile_simple()
    {
        $query = new BulkInsertQuery($this->connection);
        $query
            ->into('person')
            ->values([
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ])
        ;

        $compiled = $this->compiler->compileInsert($query);

        $this->assertEquals($this->connection->prepare('INSERT INTO person(first_name, last_name) VALUES (?, ?)'), $compiled);
        $this->assertEquals(['John', 'Doe'], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compile_bulk()
    {
        $query = new BulkInsertQuery($this->connection);
        $query
            ->bulk()
            ->into('person')
            ->values([
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ])
            ->values([
                'first_name' => 'Mickey',
                'last_name'  => 'Mouse'
            ])
        ;

        $compiled = $this->compiler->compileInsert($query);

        $this->assertEquals($this->connection->prepare('INSERT INTO person(first_name, last_name) VALUES (?, ?), (?, ?)'), $compiled);
        $this->assertEquals(['John', 'Doe', 'Mickey', 'Mouse'], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compile_replace()
    {
        $query = new BulkInsertQuery($this->connection);
        $query
            ->replace()
            ->into('person')
            ->values([
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ])
        ;

        $compiled = $this->compiler->compileInsert($query);

        $this->assertEquals($this->connection->prepare('REPLACE INTO person(first_name, last_name) VALUES (?, ?)'), $compiled);
    }

    /**
     *
     */
    public function test_compile_ignore()
    {
        $query = new BulkInsertQuery($this->connection);
        $query
            ->ignore()
            ->into('person')
            ->values([
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ])
        ;

        $compiled = $this->compiler->compileInsert($query);

        $this->assertEquals($this->connection->prepare('INSERT OR IGNORE INTO person(first_name, last_name) VALUES (?, ?)'), $compiled);
    }

    /**
     *
     */
    public function test_compile_useQuoteIdentifier()
    {
        $query = new BulkInsertQuery($this->connection);
        $query
            ->into('person')
            ->values([
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ])
        ;

        $query->useQuoteIdentifier();

        $compiled = $this->compiler->compileInsert($query);

        $this->assertEquals($this->connection->prepare('INSERT INTO "person"("first_name", "last_name") VALUES (?, ?)'), $compiled);
    }

    /**
     *
     */
    public function test_compile_columns_not_match_with_values()
    {
        $query = new BulkInsertQuery($this->connection);
        $query
            ->into('person')
            ->columns(['id', 'first_name', 'last_name', 'age'])
            ->values([
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ])
        ;

        $compiled = $this->compiler->compileInsert($query);

        $this->assertEquals($this->connection->prepare('INSERT INTO person(id, first_name, last_name, age) VALUES (?, ?, ?, ?)'), $compiled);
        $this->assertEquals([null, 'John', 'Doe', null], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compile_columns_not_match_with_values_on_bulk()
    {
        $query = new BulkInsertQuery($this->connection);
        $query
            ->bulk()
            ->into('person')
            ->columns(['id', 'first_name', 'last_name', 'age'])
            ->values([
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ])
            ->values([
                'first_name' => 'Mickey',
                'last_name'  => 'Mouse',
                'age'        => 90
            ])
        ;

        $compiled = $this->compiler->compileInsert($query);

        $this->assertEquals($this->connection->prepare('INSERT INTO person(id, first_name, last_name, age) VALUES (?, ?, ?, ?), (?, ?, ?, ?)'), $compiled);
        $this->assertEquals([null, 'John', 'Doe', null, null, 'Mickey', 'Mouse', 90], $this->compiler->getBindings($query));
    }

    /**
     *
     */
    public function test_compile_with_typed_columns()
    {
        $query = new BulkInsertQuery($this->connection);
        $query
            ->into('person')
            ->columns([
                'first_name' => 'string',
                'last_name'  => 'string',
                'birthday'   => 'date'
            ])
            ->values([
                'first_name' => 'Mickey',
                'last_name'  => 'Mouse',
                'birthday'   => new \DateTime('1928-11-18')
            ])
        ;

        $this->compiler->compileInsert($query);

        $this->assertSame(['Mickey', 'Mouse', '1928-11-18'], $this->compiler->getBindings($query));
    }

    /**
     * @testWith [true]
     *           [false]
     */
    public function test_compile_with_null($bulk)
    {
        $this->pack()->declareEntity(TestEntity::class);

        $query = new BulkInsertQuery($this->connection, new OrmPreprocessor(TestEntity::repository()));
        $query
            ->into('test_')
            ->bulk($bulk)
            ->values([
                'name' => 'Foo',
                'dateInsert' => null,
            ])
        ;

        $compiled = $this->compiler->compileInsert($query);

        $this->assertEquals($this->connection->prepare('INSERT INTO test_(name, date_insert) VALUES (?, ?)'), $compiled);
        $this->assertSame(['Foo', null], $this->compiler->getBindings($query));
    }

    /**
     * @testWith [true]
     *           [false]
     */
    public function test_compile_with_custom_null_type($bulk)
    {
        $this->prime()->types()->register(new FooType());
        $this->pack()->declareEntity(MyCustomNullableEntity::class);

        $query = new BulkInsertQuery($this->connection, new OrmPreprocessor(MyCustomNullableEntity::repository()));
        $query
            ->into('my_custom_nullable')
            ->bulk($bulk)
            ->values(['foo' => null])
        ;

        $compiled = $this->compiler->compileInsert($query);

        $this->assertEquals($this->connection->prepare('INSERT INTO my_custom_nullable(foo) VALUES (?)'), $compiled);
        $this->assertSame(['0'], $this->compiler->getBindings($query));
    }
}
