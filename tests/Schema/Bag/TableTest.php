<?php

namespace Bdf\Prime\Schema\Bag;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\Schema\Adapter\NamedIndex;
use Bdf\Prime\Schema\Constraint\Check;
use Bdf\Prime\Schema\Constraint\ConstraintSet;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Types\TypeInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class TableTest extends TestCase
{
    /**
     * @var Table
     */
    private $table;


    /**
     *
     */
    protected function setUp(): void
    {
        $this->table = new Table(
            'table_',
            [
                new Column('id_', new SqlStringType(new DummyPlatform(), TypeInterface::BIGINT), null, 0, true, false, false, false, null, null, null),
                new Column('name_', new SqlStringType(new DummyPlatform(), TypeInterface::STRING), null, 32, false, false, false, false, null, null, null),
            ],
            new IndexSet([
                new Index(['id_' => []], IndexInterface::TYPE_PRIMARY, 'PRIMARY'),
                new NamedIndex(new Index(['name_' => []]), 'table_')
            ]),
            new ConstraintSet([
                new Check('id_ != name_', 'chk_name')
            ]),
            [
                'foo' => 'bar'
            ]
        );
    }

    /**
     *
     */
    public function test_name()
    {
        $this->assertEquals('table_', $this->table->name());
    }

    /**
     *
     */
    public function test_column()
    {
        $this->assertEquals('id_', $this->table->column('id_')->name());
        $this->assertTrue($this->table->column('id_')->autoIncrement());
        $this->assertEquals(32, $this->table->column('name_')->length());
    }

    /**
     *
     */
    public function test_columns()
    {
        $this->assertCount(2, $this->table->columns());
        $this->assertContainsOnly(Column::class, $this->table->columns());
    }

    /**
     *
     */
    public function test_indexes()
    {
        $this->assertInstanceOf(IndexSet::class, $this->table->indexes());
        $this->assertEquals(['id_'], $this->table->indexes()->primary()->fields());
    }

    /**
     *
     */
    public function test_options()
    {
        $this->assertEquals(['foo' => 'bar'], $this->table->options());
        $this->assertEquals('bar', $this->table->option('foo'));
    }

    /**
     *
     */
    public function test_constraints()
    {
        $this->assertInstanceOf(ConstraintSet::class, $this->table->constraints());
        $this->assertEquals(new Check('id_ != name_', 'chk_name'), $this->table->constraints()->get('chk_name'));

    }
}
