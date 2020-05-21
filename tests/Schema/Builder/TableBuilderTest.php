<?php

namespace Bdf\Prime\Schema\Builder;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\Sql\Types\SqlIntegerType;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\Schema\Adapter\NamedIndex;
use Bdf\Prime\Schema\Bag\Column;
use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\Bag\Table;
use Bdf\Prime\Schema\Constraint\ConstraintSet;
use Bdf\Prime\Schema\Constraint\ForeignKey;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Types\TypeInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class TableBuilderTest extends TestCase
{
    /**
     * @var TableBuilder
     */
    private $builder;


    /**
     *
     */
    protected function setUp(): void
    {
        $this->builder = new TableBuilder('table_');
    }

    /**
     *
     */
    public function test_build_default()
    {
        $table = $this->builder->build();

        $this->assertInstanceOf(Table::class, $table);
        $this->assertEquals('table_', $table->name());
        $this->assertEmpty($table->columns());
        $this->assertEmpty($table->indexes()->all());
    }

    /**
     *
     */
    public function test_name()
    {
        $this->assertSame($this->builder, $this->builder->name('other_'));
        $this->assertEquals('other_', $this->builder->build()->name());
    }

    /**
     *
     */
    public function test_options()
    {
        $this->assertSame($this->builder, $this->builder->options(['foo' => 'bar']));
        $this->assertEquals(['foo' => 'bar'], $this->builder->build()->options());
    }

//    /**
//     *
//     */
//    public function test_options()
//    {
//        $this->assertSame($this->builder, $this->builder->options(['foo' => 'bar']));
//        $this->assertEquals(['foo' => 'bar'], $this->builder->build()->options());
//    }

    /**
     *
     */
    public function test_add()
    {
        $columnBuilder = $this->builder->add('name_', new SqlStringType(new DummyPlatform(), TypeInterface::STRING));

        $this->assertInstanceOf(ColumnBuilder::class, $columnBuilder);
        $this->assertEquals('name_', $columnBuilder->getName());

        $table = $this->builder->build();

        $this->assertEquals([
            'name_' => new Column('name_', new SqlStringType(new DummyPlatform(), TypeInterface::STRING), null, null, false, false, false, false, null, null, null)
        ], $table->columns());
    }

    /**
     *
     */
    public function test_index()
    {
        $this->assertSame($this->builder, $this->builder->index(['name_'], IndexInterface::TYPE_UNIQUE, 'idx1'));
        $this->assertEquals([
            'idx1' => new NamedIndex(new Index(['name_' => []], IndexInterface::TYPE_UNIQUE, 'idx1'), 'table_')
        ], $this->builder->build()->indexes()->all());
    }

    /**
     *
     */
    public function test_index_with_options()
    {
        $this->assertSame($this->builder, $this->builder->index(['name_' => ['length' => 12]], IndexInterface::TYPE_UNIQUE, 'idx1', ['opt' => 'val']));
        $this->assertEquals([
            'idx1' => new NamedIndex(new Index(['name_' => ['length' => 12]], IndexInterface::TYPE_UNIQUE, 'idx1', ['opt' => 'val']), 'table_')
        ], $this->builder->build()->indexes()->all());
    }

    /**
     *
     */
    public function test_primary_explicit_column()
    {
        $this->assertSame($this->builder, $this->builder->primary('id_'));
        $this->assertEquals(new NamedIndex(new Index(['id_' => []], IndexInterface::TYPE_PRIMARY), 'table_'), $this->builder->build()->indexes()->primary());
    }

    /**
     *
     */
    public function test_primary_implicit_column()
    {
        $this->builder->add('id_', new SqlIntegerType(new DummyPlatform(), TypeInterface::INTEGER));

        $this->assertSame($this->builder, $this->builder->primary());
        $this->assertEquals(new NamedIndex(new Index(['id_' => []], IndexInterface::TYPE_PRIMARY), 'table_'), $this->builder->build()->indexes()->primary());
    }

    /**
     *
     */
    public function test_column()
    {
        $id = $this->builder->add('id_', new SqlIntegerType(new DummyPlatform(), TypeInterface::INTEGER));
        $name = $this->builder->add('name_', new SqlStringType(new DummyPlatform(), TypeInterface::STRING));

        $this->assertSame($name, $this->builder->column());
        $this->assertSame($id, $this->builder->column('id_'));
    }

    /**
     *
     */
    public function test_indexes_simple_array()
    {
        $this->assertSame($this->builder, $this->builder->indexes(['id_', 'name_']));

        $this->assertEquals([
            new NamedIndex(new Index(['id_' => []]), 'table_'),
            new NamedIndex(new Index(['name_' => []]), 'table_'),
        ], array_values($this->builder->build()->indexes()->all()));
    }

    /**
     *
     */
    public function test_indexes_array_of_array()
    {
        $this->assertSame($this->builder, $this->builder->indexes([['id_', 'name_']]));

        $this->assertEquals([
            new NamedIndex(new Index(['id_' => [], 'name_' => []]), 'table_'),
        ], array_values($this->builder->build()->indexes()->all()));
    }

    /**
     *
     */
    public function test_indexes_assoc_array()
    {
        $this->assertSame($this->builder, $this->builder->indexes([
            'search' => ['first_name', 'last_name']
        ]));

        $this->assertEquals([
            new NamedIndex(new Index(['first_name' => [], 'last_name' => []], IndexInterface::TYPE_SIMPLE, 'search'), 'table_'),
        ], array_values($this->builder->build()->indexes()->all()));
    }

    /**
     *
     */
    public function test_indexes_with_type()
    {
        $this->assertSame($this->builder, $this->builder->indexes([
            'search' => [
                'fields' => ['first_name', 'last_name'],
                'type'   => IndexInterface::TYPE_UNIQUE
            ]
        ]));

        $this->assertEquals([
            new NamedIndex(new Index(['first_name' => [], 'last_name' => []], IndexInterface::TYPE_UNIQUE, 'search'), 'table_'),
        ], array_values($this->builder->build()->indexes()->all()));
    }

    /**
     *
     */
    public function test_indexes_with_options()
    {
        $this->assertSame($this->builder, $this->builder->indexes([
            'search' => [
                'fields'  => ['first_name', 'last_name'],
                'options' => ['fulltext' => true]
            ]
        ]));

        $this->assertEquals([
            new NamedIndex(new Index(['first_name' => [], 'last_name' => []], IndexInterface::TYPE_SIMPLE, 'search', ['fulltext' => true]), 'table_'),
        ], array_values($this->builder->build()->indexes()->all()));
    }

    /**
     *
     */
    public function test_indexes_with_fieldOptions()
    {
        $this->assertSame($this->builder, $this->builder->indexes([
            'search' => ['first_name' => ['length' => 12], 'last_name']
        ]));

        $this->assertEquals([
            new NamedIndex(new Index(['first_name' => ['length' => 12], 'last_name' => []], IndexInterface::TYPE_SIMPLE, 'search'), 'table_'),
        ], array_values($this->builder->build()->indexes()->all()));
    }

    /**
     *
     */
    public function test_buildIndexes_from_columns()
    {
        $this->builder->add('first_name', new SqlStringType(new DummyPlatform(), TypeInterface::STRING))->unique('name');
        $this->builder->add('last_name', new SqlStringType(new DummyPlatform(), TypeInterface::STRING))->unique('name');
        $this->builder->add('id_', new SqlIntegerType(new DummyPlatform(), TypeInterface::INTEGER))->unique();

        $this->assertEquals([
            new NamedIndex(new Index(['first_name' => [], 'last_name' => []], IndexInterface::TYPE_UNIQUE, 'name'), 'table_'),
            new NamedIndex(new Index(['id_' => []], IndexInterface::TYPE_UNIQUE), 'table_'),
        ], array_values($this->builder->build()->indexes()->all()));
    }

    /**
     *
     */
    public function test_fromTable()
    {
        $table = new Table(
            'unknown_table_',
            [
                new Column('col_', new SqlStringType(new DummyPlatform(), TypeInterface::STRING), null, 32, false, false, false, false, null, null, null),
                new Column('col2_', new SqlStringType(new DummyPlatform(), TypeInterface::STRING), null, 32, false, false, false, false, null, null, null)
            ],
            new IndexSet([
                new NamedIndex(new Index(['col_' => []], Index::TYPE_PRIMARY, 'PRIMARY'), 'unknown_table_'),
                new NamedIndex(new Index(['col2_' => ['length' => 12]], Index::TYPE_SIMPLE, 'idx', ['opt' => 'val']), 'unknown_table_')
            ])
        );

        $this->assertEquals($table, TableBuilder::fromTable($table)->build());
    }

    /**
     *
     */
    public function test_foreignKey()
    {
        $this->assertSame($this->builder, $this->builder->foreignKey('user_', ['user_id'], ['id_']));

        $this->assertEquals(
            new ConstraintSet([
                new ForeignKey(['user_id'], 'user_', ['id_'])
            ]),
            $this->builder->build()->constraints()
        );
    }
}
