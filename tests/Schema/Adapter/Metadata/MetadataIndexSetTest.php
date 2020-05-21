<?php

namespace Bdf\Prime\Schema\Adapter\Metadata;

use Bdf\Prime\EntityWithIndex;
use Bdf\Prime\EntityWithIndexV15;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Adapter\NamedIndex;
use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\IndexInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MetadataIndexSetTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var MetadataIndexSet
     */
    private $indexSet;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->metadata = EntityWithIndex::repository()->metadata();
        $this->indexSet = new MetadataIndexSet($this->metadata);
    }

    /**
     *
     */
    public function test_primary()
    {
        $primary = $this->indexSet->primary();

        $this->assertInstanceOf(MetadataPrimaryKeyIndex::class, $primary);
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

        $this->assertCount(4, $all);

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
    public function test_unamed_indexes()
    {
        $all = $this->indexSet->all();

        $this->assertCount(4, $all);

        $this->assertContainsOnly('string', array_keys($all));
        $this->assertContainsOnly(IndexInterface::class, $all);

        foreach ($all as $index) {
            if ($index->fields() == ['address_', 'zip_code']) {
                $this->assertStringStartsWith('IDX_', $index->name());
            }

            if ($index->fields() == ['guid_']) {
                $this->assertTrue($index->unique());
                $this->assertStringStartsWith('UNIQ_', $index->name());
            }
        }
    }

    /**
     *
     */
    public function test_get_primary()
    {
        $this->assertInstanceOf(MetadataPrimaryKeyIndex::class, $this->indexSet->get('PRIMARY'));
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

    /**
     *
     */
    public function test_get_metadata_using_IndexBuilder()
    {
        $this->indexSet = new MetadataIndexSet(EntityWithIndexV15::repository()->metadata());

        $this->assertEquals([
            new NamedIndex(new Index(['guid_' => []], Index::TYPE_UNIQUE, 0, []), 'test_entity_with_indexes_v15_'),
            new NamedIndex(new Index(['first_name' => [], 'last_name' => []], Index::TYPE_UNIQUE, 'name', []), 'test_entity_with_indexes_v15_'),
            new NamedIndex(new Index(['address_' => ['length' => 24], 'zip_code' => []], Index::TYPE_SIMPLE, 1, []), 'test_entity_with_indexes_v15_'),
            new MetadataPrimaryKeyIndex(EntityWithIndexV15::repository()->metadata()->primary),
        ], array_values($this->indexSet->all()));
    }
}
