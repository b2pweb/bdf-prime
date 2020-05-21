<?php

namespace Bdf\Prime\Schema\Bag;

use Bdf\Prime\Schema\IndexInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class IndexTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $index = new Index(['foo' => ['length' => 12], 'bar' => []], IndexInterface::TYPE_SIMPLE, 'index_name', ['fulltext' => true]);

        $this->assertEquals(['foo', 'bar'], $index->fields());
        $this->assertEquals(IndexInterface::TYPE_SIMPLE, $index->type());
        $this->assertEquals('index_name', $index->name());
        $this->assertSame(['fulltext' => true], $index->options());
        $this->assertSame(['length' => 12], $index->fieldOptions('foo'));
        $this->assertSame([], $index->fieldOptions('bar'));
    }

    /**
     *
     */
    public function test_primary()
    {
        $this->assertFalse((new Index([], IndexInterface::TYPE_SIMPLE))->primary());
        $this->assertTrue((new Index([], IndexInterface::TYPE_PRIMARY))->primary());
    }

    /**
     *
     */
    public function test_unique()
    {
        $this->assertFalse((new Index([], IndexInterface::TYPE_SIMPLE))->unique());
        $this->assertTrue((new Index([], IndexInterface::TYPE_UNIQUE))->unique());
        $this->assertTrue((new Index([], IndexInterface::TYPE_PRIMARY))->unique());
    }

    /**
     *
     */
    public function test_isComposite()
    {
        $this->assertFalse((new Index(['foo' => []]))->isComposite());
        $this->assertTrue((new Index(['foo' => [], 'bar' => []]))->isComposite());
    }
}
