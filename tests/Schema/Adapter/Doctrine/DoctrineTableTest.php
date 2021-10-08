<?php

namespace Bdf\Prime\Schema\Adapter;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Adapter\Doctrine\DoctrineTable;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Schema\ConstraintSetInterface;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\IndexSetInterface;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesRegistryInterface;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DoctrineTableTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var DoctrineTable
     */
    private $table;

    /**
     * @var TypesRegistryInterface
     */
    private $types;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->types = $this->prime()->connection('test')->platform()->types();

        $this->table = new DoctrineTable(new Table(
            'table_',
            [
                new Column('id_', Type::getType(Types::BIGINT), ['autoincrement' => true]),
                new Column('first_name', Type::getType(Types::STRING), ['notnull' => false, 'length' => 24]),
                new Column('last_name', Type::getType(Types::STRING), ['notnull' => false, 'length' => 32]),
            ],
            [
                new Index('PRIMARY', ['id_'], true, true),
                new Index('NAME', ['first_name', 'last_name'], true)
            ],
            [],
            [
                new ForeignKeyConstraint(['first_name', 'last_name'], 'contact_', ['name1_', 'name2_'], 'fk_contact')
            ],
            ['foo' => 'bar']
        ), $this->types);
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
    public function test_column_id()
    {
        $column = $this->table->column('id_');

        $this->assertInstanceOf(ColumnInterface::class, $column);
        $this->assertEquals('id_', $column->name());
        $this->assertEquals(TypeInterface::BIGINT, $column->type()->name());
        $this->assertTrue($column->autoIncrement());
        $this->assertFalse($column->nillable());
        $this->assertFalse($column->fixed());
    }

    /**
     *
     */
    public function test_column_first_name()
    {
        $column = $this->table->column('first_name');

        $this->assertInstanceOf(ColumnInterface::class, $column);
        $this->assertEquals('first_name', $column->name());
        $this->assertEquals(TypeInterface::STRING, $column->type()->name());
        $this->assertTrue($column->nillable());
        $this->assertEquals(24, $column->length());
    }

    /**
     *
     */
    public function test_columns()
    {
        $columns = $this->table->columns();

        $this->assertContainsOnly(ColumnInterface::class, $columns);
        $this->assertCount(3, $columns);
    }

    /**
     *
     */
    public function test_primary()
    {
        $primary = $this->table->primary();

        $this->assertInstanceOf(IndexInterface::class, $primary);
        $this->assertEquals(['id_'], $primary->fields());
        $this->assertTrue($primary->primary());
    }

    /**
     *
     */
    public function test_indexes()
    {
        $indexes = $this->table->indexes();

        $this->assertInstanceOf(IndexSetInterface::class, $indexes);
        $this->assertCount(2, $indexes->all());

        $this->assertEquals(['first_name', 'last_name'], $indexes->get('name')->fields());
        $this->assertEquals(['id_'], $indexes->get('PRIMARY')->fields());
    }

    /**
     *
     */
    public function test_options()
    {
        $this->assertEquals(['foo' => 'bar', 'create_options' => []], $this->table->options());
        $this->assertEquals('bar', $this->table->option('foo'));
    }

    /**
     *
     */
    public function test_constraints()
    {
        $this->assertInstanceOf(ConstraintSetInterface::class, $this->table->constraints());
        $this->assertCount(1, $this->table->constraints()->all());

        $this->assertEquals('fk_contact', $this->table->constraints()->get('fk_contact')->name());
        $this->assertEquals(['name1_', 'name2_'], $this->table->constraints()->get('fk_contact')->referred());
    }
}
