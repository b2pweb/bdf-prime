<?php

namespace Bdf\Prime\Schema\Constraint;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ForeignKeyTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $fk = new ForeignKey(['user_id'], 'user_', ['id_'], 'fk_user_id', ForeignKey::MODE_SET_NULL, ForeignKey::MODE_CASCADE, ForeignKey::MATCH_FULL);

        $this->assertEquals('fk_user_id', $fk->name());
        $this->assertEquals(['user_id'], $fk->fields());
        $this->assertEquals('user_', $fk->table());
        $this->assertEquals(['id_'], $fk->referred());
        $this->assertEquals(ForeignKey::MODE_SET_NULL, $fk->onDelete());
        $this->assertEquals(ForeignKey::MODE_CASCADE, $fk->onUpdate());
        $this->assertEquals(ForeignKey::MATCH_FULL, $fk->match());
    }

    /**
     *
     */
    public function test_visit()
    {
        $visitor = $this->createMock(ConstraintVisitorInterface::class);

        $fk = new ForeignKey([], '', []);

        $visitor->expects($this->once())
            ->method('onForeignKey')
            ->with($fk)
        ;

        $fk->visit($visitor);
    }

    /**
     *
     */
    public function test_generate_name()
    {
        $fk = new ForeignKey(['user_id'], 'user_', ['id_']);

        $this->assertStringStartsWith('FK_', $fk->name());
        $this->assertNotEquals($fk->name(), (new ForeignKey(['customer_id'], 'customer_', ['id_']))->name());
    }
}
