<?php

namespace Bdf\Prime\Schema\Adapter\Metadata;

use Bdf\Prime\EntityWithIndex;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\IndexInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MetadataPrimaryKeyIndexTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MetadataPrimaryKeyIndex
     */
    private $index;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $metadata = EntityWithIndex::repository()->metadata();
        $this->index = new MetadataPrimaryKeyIndex($metadata->primary);
    }

    /**
     *
     */
    public function test_getters()
    {
        $this->assertEquals('PRIMARY', $this->index->name());
        $this->assertEquals(IndexInterface::TYPE_PRIMARY, $this->index->type());
        $this->assertTrue($this->index->unique());
        $this->assertTrue($this->index->primary());
        $this->assertEquals(['id_'], $this->index->fields());
        $this->assertSame([], $this->index->options());
        $this->assertSame([], $this->index->fieldOptions(''));
    }
}
