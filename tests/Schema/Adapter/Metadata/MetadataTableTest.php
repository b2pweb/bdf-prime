<?php

namespace Bdf\Prime\Schema\Adapter\Metadata;

use Bdf\Prime\EntityWithIndex;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Schema\Constraint\ConstraintSet;
use Bdf\Prime\Schema\IndexSetInterface;
use Bdf\Prime\Types\TypesRegistryInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MetadataTableTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MetadataTable
     */
    private $table;

    /**
     * @var Metadata
     */
    private $metadata;

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

        $this->metadata = EntityWithIndex::repository()->metadata();
        $this->types = $this->prime()->connection('test')->platform()->types();
        $this->table = new MetadataTable($this->metadata, $this->types);
    }

    /**
     *
     */
    public function test_name()
    {
        $this->assertEquals('test_entity_with_indexes_', $this->table->name());
    }

    /**
     *
     */
    public function test_column()
    {
        $column = $this->table->column('guid_');

        $this->assertInstanceOf(ColumnInterface::class, $column);
        $this->assertEquals('guid_', $column->name());
        $this->assertEquals('string', $column->type()->name());
    }

    /**
     *
     */
    public function test_columns()
    {
        $columns = $this->table->columns();

        $this->assertContainsOnly(ColumnInterface::class, $columns);
        $this->assertCount(6, $columns);
    }

    /**
     *
     */
    public function test_primary()
    {
        $primary = $this->table->primary();

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
        $this->assertCount(4, $indexes->all());
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
        $this->assertSame(ConstraintSet::blank(), $this->table->constraints());
    }
}
