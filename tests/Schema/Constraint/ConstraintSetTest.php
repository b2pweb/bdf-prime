<?php

namespace Bdf\Prime\Schema\Constraint;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ConstraintSetTest extends TestCase
{
    /**
     *
     */
    public function test_blank()
    {
        $this->assertInstanceOf(ConstraintSet::class, ConstraintSet::blank());
        $this->assertEmpty(ConstraintSet::blank()->all());
    }

    /**
     *
     */
    public function test_all_values()
    {
        $constraints = [
            new Check('chk1'),
            new Check('chk2')
        ];

        $set = new ConstraintSet($constraints);

        $this->assertEquals($constraints, array_values($set->all()));
    }

    /**
     *
     */
    public function test_all_indexes()
    {
        $constraints = [
            new Check('chk1', 'chk1'),
            new Check('chk2', 'chk2')
        ];

        $set = new ConstraintSet($constraints);

        $this->assertEquals(['CHK1', 'CHK2'], array_keys($set->all()));
    }

    /**
     *
     */
    public function test_get()
    {
        $set = new ConstraintSet([
            new Check('chk1', 'name1'),
            new Check('chk2', 'name2')
        ]);

        $this->assertEquals('chk1', $set->get('name1')->expression());
        $this->assertEquals('chk2', $set->get('name2')->expression());

        $this->assertSame($set->get('NAME1'), $set->get('Name1'));
    }

    /**
     *
     */
    public function test_apply()
    {
        $constraints = [
            new Check('chk1', 'name1'),
            new Check('chk2', 'name2')
        ];

        $set = new ConstraintSet($constraints);

        $visitor = $this->createMock(ConstraintVisitorInterface::class);

        $visitor->expects($this->exactly(2))
            ->method('onCheck')
            ->withConsecutive(
                [$constraints[0]],
                [$constraints[1]]
            )
        ;

        $set->apply($visitor);
    }
}
