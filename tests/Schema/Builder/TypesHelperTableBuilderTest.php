<?php

namespace Bdf\Prime\Schema\Builder;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\TableInterface;
use Bdf\Prime\Types\ArrayType;
use Bdf\Prime\Types\JsonType;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesRegistryInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 *
 */
class TypesHelperTableBuilderTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var TypesRegistryInterface
     */
    private $types;

    /**
     * @var TypesHelperTableBuilder
     */
    private $builder;


    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->prime()->connection('test')->getConfiguration()->getTypes()
            ->register(new ArrayType())
            ->register(new JsonType())
        ;

        $this->types = $this->prime()->connection('test')->platform()->types();
        $this->builder = new TypesHelperTableBuilder(new TableBuilder('table_'), $this->types);
    }

    /**
     * @param string $method
     * @param array $arguments
     * @param bool $returnThis
     *
     * @dataProvider delegatedMethods
     */
    public function test_delegation($method, $arguments, $returnThis = true)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->createMock(TableBuilderInterface::class);

        $builder = new TypesHelperTableBuilder($mock, $this->types);
        $return = $returnThis ? $builder : new stdClass();

        $mock->expects($this->once())
            ->method($method)
            ->with(...$arguments)
            ->willReturn($return)
        ;

        $this->assertSame($return, $builder->$method(...$arguments));
    }

    /**
     *
     */
    public function test_delegation_build()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->createMock(TableBuilderInterface::class);

        $builder = new TypesHelperTableBuilder($mock, $this->types);
        $return = $this->createMock(TableInterface::class);

        $mock->expects($this->once())
            ->method('build')
            ->willReturn($return)
        ;

        $this->assertSame($return, $builder->build());
    }

    /**
     * @return array
     */
    public function delegatedMethods()
    {
        return [
            ['name',        ['other_']],
            ['options',     [['foo' => 'bar']]],
            ['indexes',     [['id_']]],
            ['primary',     ['id_', 'PRIMARY']],
            ['add',         ['id_', new SqlStringType(new DummyPlatform(), TypeInterface::BIGINT)], false],
            ['column',      ['id_'],                                           false],
            ['foreignKey',  ['table_', [], []]],
            ['index',       ['name_']],
        ];
    }

    /**
     *
     */
    public function test_string()
    {
        $this->assertSame($this->builder, $this->builder->string('name_', 32));

        $column = $this->builder->column()->build();
        $this->assertEquals('name_', $column->name());
        $this->assertEquals(32, $column->length());
        $this->assertEquals('string', $column->type()->name());
    }

    /**
     *
     */
    public function test_text()
    {
        $this->assertSame($this->builder, $this->builder->text('name_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('name_', $column->name());
        $this->assertNull($column->length());
        $this->assertEquals('text', $column->type()->name());
    }

    /**
     *
     */
    public function test_integer()
    {
        $this->assertSame($this->builder, $this->builder->integer('number_')->autoincrement(true)->unsigned(true));

        $column = $this->builder->column()->build();
        $this->assertEquals('number_', $column->name());
        $this->assertTrue($column->unsigned());
        $this->assertTrue($column->autoIncrement());
        $this->assertEquals('integer', $column->type()->name());
    }

    /**
     *
     */
    public function test_tinyint()
    {
        $this->assertSame($this->builder, $this->builder->tinyint('number_')->autoincrement(true)->unsigned(true));

        $column = $this->builder->column()->build();
        $this->assertEquals('number_', $column->name());
        $this->assertTrue($column->unsigned());
        $this->assertTrue($column->autoIncrement());
        $this->assertEquals('tinyint', $column->type()->name());
    }

    /**
     *
     */
    public function test_smallint()
    {
        $this->assertSame($this->builder, $this->builder->smallint('number_')->autoincrement(true)->unsigned(true));

        $column = $this->builder->column()->build();
        $this->assertEquals('number_', $column->name());
        $this->assertTrue($column->unsigned());
        $this->assertTrue($column->autoIncrement());
        $this->assertEquals('smallint', $column->type()->name());
    }

    /**
     *
     */
    public function test_bigint()
    {
        $this->assertSame($this->builder, $this->builder->bigint('number_')->autoincrement(true)->unsigned(true));

        $column = $this->builder->column()->build();
        $this->assertEquals('number_', $column->name());
        $this->assertTrue($column->unsigned());
        $this->assertTrue($column->autoIncrement());
        $this->assertEquals('bigint', $column->type()->name());
    }

    /**
     *
     */
    public function test_unsignedTinyint()
    {
        $this->assertSame($this->builder, $this->builder->unsignedTinyint('number_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('number_', $column->name());
        $this->assertTrue($column->unsigned());
        $this->assertEquals('tinyint', $column->type()->name());
    }

    /**
     *
     */
    public function test_unsignedSmallint()
    {
        $this->assertSame($this->builder, $this->builder->unsignedSmallint('number_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('number_', $column->name());
        $this->assertTrue($column->unsigned());
        $this->assertEquals('smallint', $column->type()->name());
    }

    /**
     *
     */
    public function test_unsignedInteger()
    {
        $this->assertSame($this->builder, $this->builder->unsignedInteger('number_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('number_', $column->name());
        $this->assertTrue($column->unsigned());
        $this->assertEquals('integer', $column->type()->name());
    }

    /**
     *
     */
    public function test_unsignedBigint()
    {
        $this->assertSame($this->builder, $this->builder->unsignedBigint('number_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('number_', $column->name());
        $this->assertTrue($column->unsigned());
        $this->assertEquals('bigint', $column->type()->name());
    }

    /**
     *
     */
    public function test_float()
    {
        $this->assertSame($this->builder, $this->builder->float('number_')->precision(5, 2));

        $column = $this->builder->column()->build();
        $this->assertEquals('number_', $column->name());
        $this->assertEquals(5, $column->precision());
        $this->assertEquals(2, $column->scale());
        $this->assertEquals('float', $column->type()->name());
    }

    /**
     *
     */
    public function test_double()
    {
        $this->assertSame($this->builder, $this->builder->double('number_')->precision(5, 2));

        $column = $this->builder->column()->build();
        $this->assertEquals('number_', $column->name());
        $this->assertEquals(5, $column->precision());
        $this->assertEquals(2, $column->scale());
        $this->assertEquals('double', $column->type()->name());
    }

    /**
     *
     */
    public function test_boolean()
    {
        $this->assertSame($this->builder, $this->builder->boolean('valid_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('valid_', $column->name());
        $this->assertEquals('boolean', $column->type()->name());
    }

    /**
     *
     */
    public function test_boolean_with_default_value_must_be_converted_to_db_value()
    {
        $this->assertSame($this->builder, $this->builder->boolean('valid_', false));

        $column = $this->builder->column()->build();
        $this->assertSame(0, $column->defaultValue());
    }

    /**
     *
     */
    public function test_datetime()
    {
        $this->assertSame($this->builder, $this->builder->datetime('at_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('at_', $column->name());
        $this->assertEquals('datetime', $column->type()->name());
    }

    /**
     *
     */
    public function test_binary()
    {
        $this->assertSame($this->builder, $this->builder->binary('at_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('at_', $column->name());
        $this->assertEquals('binary', $column->type()->name());
    }

    /**
     *
     */
    public function test_blob()
    {
        $this->assertSame($this->builder, $this->builder->blob('data_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('data_', $column->name());
        $this->assertEquals('blob', $column->type()->name());
    }

    /**
     *
     */
    public function test_json()
    {
        $this->assertSame($this->builder, $this->builder->json('data_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('data_', $column->name());
        $this->assertEquals('text', $column->type()->name());
    }

    /**
     *
     */
    public function test_simpleArray()
    {
        $this->assertSame($this->builder, $this->builder->simpleArray('data_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('data_', $column->name());
        $this->assertEquals('text', $column->type()->name());
    }

    /**
     *
     */
    public function test_object()
    {
        $this->assertSame($this->builder, $this->builder->object('data_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('data_', $column->name());
        $this->assertEquals('text', $column->type()->name());
    }

    /**
     *
     */
    public function test_array_object()
    {
        $this->assertSame($this->builder, $this->builder->arrayObject('data_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('data_', $column->name());
        $this->assertEquals('text', $column->type()->name());
    }

    /**
     *
     */
    public function test_arrayOf()
    {
        $this->assertSame($this->builder, $this->builder->arrayOf('data_', 'boolean'));

        $column = $this->builder->column()->build();
        $this->assertEquals('data_', $column->name());
        $this->assertEquals('text', $column->type()->name());
    }

    /**
     *
     */
    public function test_arrayOfInt()
    {
        $this->assertSame($this->builder, $this->builder->arrayOfInt('data_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('data_', $column->name());
        $this->assertEquals('text', $column->type()->name());
    }

    /**
     *
     */
    public function test_arrayOfDouble()
    {
        $this->assertSame($this->builder, $this->builder->arrayOfDouble('data_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('data_', $column->name());
        $this->assertEquals('text', $column->type()->name());
    }

    /**
     *
     */
    public function test_arrayOfDateTime()
    {
        $this->assertSame($this->builder, $this->builder->arrayOfDateTime('data_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('data_', $column->name());
        $this->assertEquals('text', $column->type()->name());
    }

    /**
     *
     */
    public function test_searchableArray()
    {
        $this->assertSame($this->builder, $this->builder->searchableArray('data_'));

        $column = $this->builder->column()->build();
        $this->assertEquals('data_', $column->name());
        $this->assertEquals('text', $column->type()->name());
    }

    /**
     *
     */
    public function test_autoincrement()
    {
        $this->builder->bigint('id_');

        $this->assertSame($this->builder, $this->builder->autoincrement());

        $column = $this->builder->column()->build();
        $this->assertTrue($column->autoIncrement());
    }

    /**
     *
     */
    public function test_nillable()
    {
        $this->builder->bigint('id_');

        $this->assertSame($this->builder, $this->builder->nillable());

        $column = $this->builder->column()->build();
        $this->assertTrue($column->nillable());
    }
}
