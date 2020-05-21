<?php

namespace Bdf\Prime\Schema\Builder;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\Sql\Types\SqlBooleanType;
use Bdf\Prime\Platform\Sql\Types\SqlIntegerType;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\Schema\Bag\Column;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Types\TypeInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ColumnBuilderTest extends TestCase
{
    /**
     * @var ColumnBuilder
     */
    private $builder;


    /**
     *
     */
    protected function setUp(): void
    {
        $this->builder = new ColumnBuilder('id_', new SqlStringType(new DummyPlatform(), TypeInterface::BIGINT));
    }

    /**
     *
     */
    public function test_getters()
    {
        $this->assertEquals([], $this->builder->indexes());
        $this->assertEquals('id_', $this->builder->getName());
    }

    /**
     *
     */
    public function test_build_defaults()
    {
        $column = $this->builder->build();

        $this->assertInstanceOf(Column::class, $column);
        $this->assertEquals('id_', $column->name());
        $this->assertEquals(new SqlStringType(new DummyPlatform(), TypeInterface::BIGINT), $column->type());
        $this->assertNull($column->defaultValue());
        $this->assertNull($column->length());
        $this->assertFalse($column->autoIncrement());
        $this->assertFalse($column->unsigned());
        $this->assertFalse($column->fixed());
        $this->assertFalse($column->nillable());
        $this->assertNull($column->comment());
        $this->assertNull($column->precision());
        $this->assertNull($column->scale());
    }

    /**
     *
     */
    public function test_autoincrement()
    {
        $this->assertSame($this->builder, $this->builder->autoincrement());
        $this->assertTrue($this->builder->build()->autoIncrement());
    }

    /**
     *
     */
    public function test_length()
    {
        $this->assertSame($this->builder, $this->builder->length(15));
        $this->assertEquals(15, $this->builder->build()->length());
    }

    /**
     *
     */
    public function test_comment()
    {
        $this->assertSame($this->builder, $this->builder->comment("hello"));
        $this->assertEquals("hello", $this->builder->build()->comment());
    }

    /**
     *
     */
    public function test_default()
    {
        $this->assertSame($this->builder, $this->builder->setDefault("123"));
        $this->assertEquals("123", $this->builder->build()->defaultValue());
    }

    /**
     *
     */
    public function test_default_should_be_converted_to_dbal_value()
    {
        $this->builder = new ColumnBuilder('test', new SqlBooleanType(new DummyPlatform()));

        $this->assertSame($this->builder, $this->builder->setDefault(false));
        $this->assertSame(0, $this->builder->build()->defaultValue());
    }

    /**
     *
     */
    public function test_precision()
    {
        $this->assertSame($this->builder, $this->builder->precision(5, 2));
        $this->assertEquals(5, $this->builder->build()->precision());
        $this->assertEquals(2, $this->builder->build()->scale());
    }

    /**
     *
     */
    public function test_nillable()
    {
        $this->assertSame($this->builder, $this->builder->nillable());
        $this->assertTrue($this->builder->build()->nillable());
    }

    /**
     *
     */
    public function test_unsigned()
    {
        $this->assertSame($this->builder, $this->builder->unsigned());
        $this->assertTrue($this->builder->build()->unsigned());
    }

    /**
     *
     */
    public function test_fixed()
    {
        $this->assertSame($this->builder, $this->builder->fixed());
        $this->assertTrue($this->builder->build()->fixed());
    }

    /**
     *
     */
    public function test_name()
    {
        $this->assertSame($this->builder, $this->builder->name("hello"));
        $this->assertEquals("hello", $this->builder->build()->name());
    }

    /**
     *
     */
    public function test_options()
    {
        $this->assertSame($this->builder, $this->builder->options(['foo' => 'bar']));
        $this->assertEquals(['foo' => 'bar'], $this->builder->build()->options());
        $this->assertEquals('bar', $this->builder->build()->option('foo'));
    }

    /**
     *
     */
    public function test_type()
    {
        $this->assertSame($this->builder, $this->builder->type(new SqlIntegerType(new DummyPlatform(), 'integer')));
        $this->assertEquals(new SqlIntegerType(new DummyPlatform(), 'integer'), $this->builder->build()->type());
    }

    /**
     *
     */
    public function test_unique_default()
    {
        $this->assertSame($this->builder, $this->builder->unique());
        $this->assertEquals([IndexInterface::TYPE_UNIQUE], $this->builder->indexes());
    }

    /**
     *
     */
    public function test_unique_named()
    {
        $this->builder->unique('idx1')->unique('idx2');
        $this->assertEquals([
            'idx1' => IndexInterface::TYPE_UNIQUE,
            'idx2' => IndexInterface::TYPE_UNIQUE,
        ], $this->builder->indexes());
    }
}
