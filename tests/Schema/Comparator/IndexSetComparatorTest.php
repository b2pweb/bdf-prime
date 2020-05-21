<?php

namespace Bdf\Prime\Schema\Comparator;

use Bdf\Prime\Schema\Adapter\NamedIndex;
use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\IndexInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class IndexSetComparatorTest extends TestCase
{
    /**
     *
     */
    public function test_with_same_set()
    {
        $set = new IndexSet([
            new Index(['id_' => []], IndexInterface::TYPE_PRIMARY, 'PRIMARY'),
            new Index(['first_name' => [], 'last_name' => []], IndexInterface::TYPE_UNIQUE, 'name'),
            new NamedIndex(new Index(['address_' => [], 'zip_code' => []]), 'test_'),
        ]);

        $comparator = new IndexSetComparator($set, $set);

        $this->assertEmpty($comparator->added());
        $this->assertEmpty($comparator->removed());
        $this->assertEmpty($comparator->changed());
    }

    /**
     *
     */
    public function test_with_added()
    {
        $from = new IndexSet([
            new Index(['first_name' => [], 'last_name' => []], IndexInterface::TYPE_UNIQUE, 'name'),
        ]);

        $to = new IndexSet([
            $id = new Index(['id_' => []], IndexInterface::TYPE_PRIMARY, 'PRIMARY'),
            new Index(['first_name' => [], 'last_name' => []], IndexInterface::TYPE_UNIQUE, 'name'),
            $addr = new NamedIndex(new Index(['address_' => [], 'zip_code' => []]), 'test_'),
        ]);

        $comparator = new IndexSetComparator($from, $to);

        $this->assertEquals([$id, $addr], $comparator->added());
        $this->assertEmpty($comparator->changed());
        $this->assertEmpty($comparator->removed());
    }

    /**
     *
     */
    public function test_with_changed()
    {
        $from = new IndexSet([
            new Index(['id_' => []], IndexInterface::TYPE_PRIMARY, 'PRIMARY'),
            new Index(['first_name' => []], IndexInterface::TYPE_UNIQUE, 'name'),
            new Index(['address_' => [], 'zip_code' => []], IndexInterface::TYPE_SIMPLE, 'addr'),
        ]);

        $to = new IndexSet([
            new Index(['id_' => []], IndexInterface::TYPE_PRIMARY, 'PRIMARY'),
            $name = new Index(['first_name' => [], 'last_name' => []], IndexInterface::TYPE_UNIQUE, 'name'),
            $addr = new Index(['address_' => [], 'zip_code' => []], IndexInterface::TYPE_UNIQUE, 'addr'),
        ]);

        $comparator = new IndexSetComparator($from, $to);

        $this->assertEquals([$name, $addr], $comparator->changed());
        $this->assertEmpty($comparator->added());
        $this->assertEmpty($comparator->removed());
    }

    /**
     *
     */
    public function test_with_removed()
    {
        $from = new IndexSet([
            $id = new Index(['id_' => []], IndexInterface::TYPE_PRIMARY, 'PRIMARY'),
            new Index(['first_name' => [], 'last_name' => []], IndexInterface::TYPE_UNIQUE, 'name'),
            $addr = new Index(['address_' => [], 'zip_code' => []], IndexInterface::TYPE_SIMPLE, 'addr'),
        ]);

        $to = new IndexSet([
            new Index(['first_name' => [], 'last_name' => []], IndexInterface::TYPE_UNIQUE, 'name'),
        ]);

        $comparator = new IndexSetComparator($from, $to);

        $this->assertEmpty($comparator->changed());
        $this->assertEmpty($comparator->added());
        $this->assertEquals([$id, $addr], $comparator->removed());
    }

    /**
     *
     */
    public function test_with_primary_removed()
    {
        $from = new IndexSet([
            $id = new Index(['id_' => []], IndexInterface::TYPE_PRIMARY, 'PRIMARY'),
        ]);

        $to = new IndexSet([]);

        $comparator = new IndexSetComparator($from, $to);

        $this->assertEquals([$id], $comparator->removed());
    }

    /**
     *
     */
    public function test_with_primary_name_changed()
    {
        $from = new IndexSet([
            new Index(['id_' => []], IndexInterface::TYPE_PRIMARY, 'PRIMARY'),
        ]);

        $to = new IndexSet([
            new Index(['id_' => []], IndexInterface::TYPE_PRIMARY, 'other_name')
        ]);

        $comparator = new IndexSetComparator($from, $to);

        $this->assertEmpty($comparator->removed());
    }

    /**
     *
     */
    public function test_with_option_changed()
    {
        $from = new IndexSet([
            new Index(['first_name' => []], IndexInterface::TYPE_UNIQUE, 'name', []),
        ]);

        $to = new IndexSet([
            $name = new Index(['first_name' => []], IndexInterface::TYPE_UNIQUE, 'name', ['myopt' => 'val']),
        ]);

        $comparator = new IndexSetComparator($from, $to);

        $this->assertEquals([$name], $comparator->changed());
        $this->assertEmpty($comparator->added());
        $this->assertEmpty($comparator->removed());
    }

    /**
     *
     */
    public function test_with_fieldOptions_changed()
    {
        $from = new IndexSet([
            new Index(['first_name' => ['length' => 12]], IndexInterface::TYPE_UNIQUE, 'name', []),
        ]);

        $to = new IndexSet([
            $name = new Index(['first_name' => ['length' => 24]], IndexInterface::TYPE_UNIQUE, 'name', []),
        ]);

        $comparator = new IndexSetComparator($from, $to);

        $this->assertEquals([$name], $comparator->changed());
        $this->assertEmpty($comparator->added());
        $this->assertEmpty($comparator->removed());
    }
}
