<?php

namespace Bdf\Prime\Schema\Constraint;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class CheckTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $exp = new \stdClass();
        $name = 'name_';

        $check = new Check($exp, $name);

        $this->assertSame($exp, $check->expression());
        $this->assertSame($name, $check->name());
    }

    /**
     *
     */
    public function test_visit()
    {
        $visitor = $this->createMock(ConstraintVisitorInterface::class);

        $check = new Check('');

        $visitor->expects($this->once())
            ->method('onCheck')
            ->with($check)
        ;

        $check->visit($visitor);
    }

    /**
     *
     */
    public function test_generate_name()
    {
        $check = new Check('my exp');

        $this->assertStringStartsWith('CHK_', $check->name());

        $this->assertNotEquals($check->name(), (new Check('other'))->name());
    }
}
