<?php

namespace Bdf\Prime\Schema\Adapter\Doctrine;

use Bdf\Prime\Schema\Constraint\ConstraintVisitorInterface;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DoctrineForeignKeyTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $doctrine = new ForeignKeyConstraint(['user_id'], 'user_', ['id_'], 'fk_user_id', ['onDelete' => 'CASCADE']);
        $fk = new DoctrineForeignKey($doctrine);

        $this->assertEquals(['user_id'], $fk->fields());
        $this->assertEquals('user_', $fk->table());
        $this->assertEquals(['id_'], $fk->referred());
        $this->assertEquals('fk_user_id', $fk->name());
        $this->assertEquals('CASCADE', $fk->onDelete());
        $this->assertEquals('RESTRICT', $fk->onUpdate());
        $this->assertEquals('SIMPLE', $fk->match());
    }

    /**
     *
     */
    public function test_visit()
    {
        $visitor = $this->createMock(ConstraintVisitorInterface::class);

        $doctrine = new ForeignKeyConstraint(['user_id'], 'user_', ['id_'], 'fk_user_id', ['onDelete' => 'CASCADE']);
        $fk = new DoctrineForeignKey($doctrine);

        $visitor->expects($this->once())
            ->method('onForeignKey')
            ->with($fk)
        ;

        $fk->visit($visitor);
    }
}
