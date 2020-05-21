<?php

namespace Bdf\Prime;

use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\TableInterface;

/**
 * Class SchemaAssertion
 */
trait SchemaAssertion
{
    /**
     * @param ColumnInterface $expected
     * @param ColumnInterface $current
     */
    public function assertColumns(ColumnInterface $expected, ColumnInterface $current)
    {
        $this->assertEquals($expected->name(), $current->name(), 'name');
        $this->assertEquals($expected->type()->name(), $current->type()->name(), 'type');
        $this->assertEquals(get_class($expected->type()), get_class($current->type()), 'type');
        $this->assertEquals($expected->defaultValue(), $current->defaultValue(), 'default');
        $this->assertEquals($expected->length(), $current->length(), 'length');
        $this->assertEquals($expected->autoIncrement(), $current->autoIncrement(), 'auto increment');
        $this->assertEquals($expected->unsigned(), $current->unsigned(), 'unsigned');
        $this->assertEquals($expected->fixed(), $current->fixed(), 'fixed');
        $this->assertEquals($expected->nillable(), $current->nillable(), 'nillable');
        $this->assertEquals($expected->comment(), $current->comment(), 'comment');
        $this->assertEquals($expected->precision(), $current->precision(), 'precision');
        $this->assertEquals($expected->scale(), $current->scale(), 'scale');
    }

    /**
     * @param IndexInterface $expected
     * @param IndexInterface $current
     */
    public function assertIndex(IndexInterface $expected, IndexInterface $current)
    {
        $this->assertEquals($expected->name(), $current->name(), 'name');
        $this->assertEquals($expected->fields(), $current->fields(), 'fields');
        $this->assertEquals($expected->type(), $current->type(), 'type');
    }

    /**
     * @param TableInterface $expected
     * @param TableInterface $current
     */
    public function assertTable(TableInterface $expected, TableInterface $current)
    {
        $this->assertEquals($expected->name(), $current->name(), 'name');
        $this->assertCount(count($expected->columns()), $current->columns(), 'count columns');

        foreach ($expected->columns() as $column) {
            $this->assertColumns($column, $current->column($column->name()));
        }

        $this->assertCount(count($expected->indexes()->all()), $current->indexes()->all(), 'count indexes');
        $this->assertIndex($expected->indexes()->primary(), $current->indexes()->primary());

        foreach ($expected->indexes()->all() as $index) {
            $this->assertIndex($index, $current->indexes()->get($index->name()));
        }
    }
}
