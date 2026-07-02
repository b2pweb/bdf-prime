<?php

namespace Bdf\Prime\Schema\Adapter\Doctrine;

use Bdf\Prime\Schema\IndexInterface;
use Doctrine\DBAL\Schema\Index;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DoctrineIndexTest extends TestCase
{
    /**
     *
     */
    public function test_simple()
    {
        $index = new DoctrineIndex(new Index('name', ['col1', 'col2']), false);

        $this->assertEquals('name', $index->name());
        $this->assertEquals(['col1', 'col2'], $index->fields());
        $this->assertFalse($index->unique());
        $this->assertFalse($index->primary());
        $this->assertEquals(IndexInterface::TYPE_SIMPLE, $index->type());
        $this->assertTrue($index->isComposite());
    }

    /**
     *
     */
    public function test_unique()
    {
        $index = new DoctrineIndex(new Index('name', ['col1'], true), false);

        $this->assertEquals('name', $index->name());
        $this->assertEquals(['col1'], $index->fields());
        $this->assertTrue($index->unique());
        $this->assertFalse($index->primary());
        $this->assertEquals(IndexInterface::TYPE_UNIQUE, $index->type());
        $this->assertFalse($index->isComposite());
    }

    /**
     *
     */
    public function test_primary()
    {
        $index = new DoctrineIndex(new Index('name', ['col1'], true, true), true);

        $this->assertEquals('name', $index->name());
        $this->assertEquals(['col1'], $index->fields());
        $this->assertTrue($index->unique());
        $this->assertTrue($index->primary());
        $this->assertEquals(IndexInterface::TYPE_PRIMARY, $index->type());
        $this->assertFalse($index->isComposite());
    }

    /**
     *
     */
    public function test_options()
    {
        $index = new DoctrineIndex(new Index('name', ['col1'], false, false, ['fulltext'], ['lengths' => [12]]), false);

        $this->assertSame([
            'lengths'  => [12],
            'fulltext' => true,
        ], $index->options());
    }

    /**
     *
     */
    public function test_fieldOptions()
    {
        $index = new DoctrineIndex(new Index('name', ['col1', 'col2'], false, false, ['fulltext'], ['lengths' => [12]]), false);

        $this->assertSame(['length'  => 12], $index->fieldOptions('col1'));
        $this->assertSame([], $index->fieldOptions('col2'));

        $index = new DoctrineIndex(new Index('name', ['col1', 'col2'], true, false));
        $this->assertSame([], $index->fieldOptions('col1'));
    }
}
