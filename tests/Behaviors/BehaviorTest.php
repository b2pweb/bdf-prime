<?php

namespace Bdf\Prime\Behaviors;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Repository\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class BehaviorTest extends TestCase
{
    /**
     *
     */
    public function test_empty_constraint()
    {
        $mapper = new Behavior();

        $this->assertEquals([], $mapper->constraints());
    }

    /**
     *
     */
    public function test_empty_event()
    {
        $mapper = new Behavior();

        $notifier = $this->createMock(EntityRepository::class);
        $notifier->expects($this->never())->method($this->anything());

        $mapper->subscribe($notifier);
    }

    /**
     *
     */
    public function test_empty_schema()
    {
        $mapper = new Behavior();

        $builder = $this->createMock(FieldBuilder::class);
        $builder->expects($this->never())->method($this->anything());

        $mapper->changeSchema($builder);
    }
}
