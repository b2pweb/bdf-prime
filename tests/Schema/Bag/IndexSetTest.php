<?php

namespace Bdf\Prime\Schema\Bag;

use Bdf\Prime\Schema\Adapter\NamedIndex;
use Bdf\Prime\Schema\IndexInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class IndexSetTest extends TestCase
{
    /**
     * @var IndexSet
     */
    private $indexSet;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->indexSet = new IndexSet([
            new Index(['id_' => []], IndexInterface::TYPE_PRIMARY, 'PRIMARY'),
            new Index(['first_name' => [], 'last_name' => []], IndexInterface::TYPE_UNIQUE, 'name'),
            new NamedIndex(new Index(['address_' => [], 'zip_code' => []]), 'test_'),
        ]);
    }

    /**
     *
     */
    public function test_primary()
    {
        $primary = $this->indexSet->primary();

        $this->assertInstanceOf(IndexInterface::class, $primary);
        $this->assertEquals('PRIMARY', $primary->name());
        $this->assertEquals(['id_'], $primary->fields());
        $this->assertTrue($primary->primary());
    }

    /**
     *
     */
    public function test_all()
    {
        $all = $this->indexSet->all();

        $this->assertCount(3, $all);

        $this->assertContainsOnly(IndexInterface::class, $all);

        $found = false;

        foreach ($all as $index) {
            if ($index->fields() == ['address_', 'zip_code']) {
                $found = true;

                $this->assertFalse($index->unique());
                $this->assertEquals(0, $index->type());
            }
        }

        $this->assertTrue($found);
    }

    /**
     *
     */
    public function test_get_primary()
    {
        $index = $this->indexSet->get('PRIMARY');

        $this->assertInstanceOf(IndexInterface::class, $index);
        $this->assertTrue($index->primary());
        $this->assertEquals($index, $this->indexSet->primary());
    }

    /**
     *
     */
    public function test_get_will_not_consider_case()
    {
        $this->assertInstanceOf(IndexInterface::class, $this->indexSet->get('PriMarY'));
        $this->assertInstanceOf(IndexInterface::class, $this->indexSet->get('NAME'));
    }

    /**
     *
     */
    public function test_get_unique()
    {
        $unique = $this->indexSet->get('name');

        $this->assertTrue($unique->unique());
        $this->assertEquals(['first_name', 'last_name'], $unique->fields());
    }

    /**
     *
     */
    public function test_has()
    {
        $this->assertTrue($this->indexSet->has('name'));
        $this->assertTrue($this->indexSet->has('primary'));
        $this->assertFalse($this->indexSet->has('not_found'));
    }
}
