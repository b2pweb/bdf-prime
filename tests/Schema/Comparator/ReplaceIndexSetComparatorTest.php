<?php

namespace Bdf\Prime\Schema\Comparator;

use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\IndexInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ReplaceIndexSetComparatorTest extends TestCase
{
    /**
     *
     */
    public function test_with_changed()
    {
        $from = new IndexSet([
            new Index(['id_' => []], IndexInterface::TYPE_PRIMARY, 'PRIMARY'),
            $oldName = new Index(['first_name' => []], IndexInterface::TYPE_UNIQUE, 'name'),
            $oldAddr = new Index(['address_' => [], 'zip_code' => []], IndexInterface::TYPE_SIMPLE, 'addr'),
        ]);

        $to = new IndexSet([
            new Index(['id_' => []], IndexInterface::TYPE_PRIMARY, 'PRIMARY'),
            $newName = new Index(['first_name' => [], 'last_name' => []], IndexInterface::TYPE_UNIQUE, 'name'),
            $newAddr = new Index(['address_' => [], 'zip_code' => []], IndexInterface::TYPE_UNIQUE, 'addr'),
        ]);

        $comparator = new ReplaceIndexSetComparator(
            new IndexSetComparator($from, $to)
        );

        $this->assertEmpty($comparator->changed());
        $this->assertEquals([$newName, $newAddr], $comparator->removed());
        $this->assertEquals([$newName, $newAddr], $comparator->added());
    }
}
