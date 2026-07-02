<?php

namespace Bdf\Prime\Schema\Adapter\Doctrine;

use Bdf\Prime\Schema\IndexInterface;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DoctrinePrimaryKeyIndexTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $index = new DoctrinePrimaryKeyIndex(PrimaryKeyConstraint::editor()->setUnquotedName('name')->setUnquotedColumnNames('col1')->create());

        $this->assertEquals('name', $index->name());
        $this->assertEquals(['col1'], $index->fields());
        $this->assertTrue($index->unique());
        $this->assertTrue($index->primary());
        $this->assertEquals(IndexInterface::TYPE_PRIMARY, $index->type());
        $this->assertFalse($index->isComposite());
    }

    /**
     * A PK without an explicit name should fall back to 'primary'
     */
    public function test_name_defaults_to_primary_when_no_name_is_set()
    {
        $index = new DoctrinePrimaryKeyIndex(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('col1')->create());

        $this->assertEquals('primary', $index->name());
    }

    /**
     *
     */
    public function test_composite_index()
    {
        $index = new DoctrinePrimaryKeyIndex(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('col1', 'col2')->create());

        $this->assertTrue($index->isComposite());
        $this->assertSame([], $index->fieldOptions('col1'));
        $this->assertSame([], $index->fieldOptions('col2'));
    }

    /**
     * A clustered PK should return an empty options array
     */
    public function test_options_clustered_returns_empty()
    {
        $index = new DoctrinePrimaryKeyIndex(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('col1')->setIsClustered(true)->create()
        );

        $this->assertSame([], $index->options());
    }

    /**
     * A non-clustered PK should advertise itself via the 'nonclustered' option
     */
    public function test_options_nonclustered_returns_flag()
    {
        $index = new DoctrinePrimaryKeyIndex(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('col1')->setIsClustered(false)->create()
        );

        $this->assertSame(['nonclustered' => true], $index->options());
    }
}
