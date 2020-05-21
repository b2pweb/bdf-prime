<?php

namespace Bdf\Prime\Schema\Adapter;

use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Schema\Constraint\ConstraintSet;
use Bdf\Prime\Schema\TableInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ConstraintTableTest extends TestCase
{
    /**
     *
     */
    public function test_constraints()
    {
        $mock = $this->createMock(TableInterface::class);

        $constraints = new ConstraintSet([]);
        $table = new ConstraintTable($mock, $constraints);

        $this->assertSame($constraints, $table->constraints());
    }

    /**
     * @dataProvider delegatedMethods
     */
    public function test_delegate($method, $parameters, $return)
    {
        $mock = $this->createMock(TableInterface::class);

        $table = new ConstraintTable($mock, ConstraintSet::blank());

        $mock->expects($this->once())
            ->method($method)
            ->with(...$parameters)
            ->willReturn($return)
        ;

        $this->assertSame($return, $table->$method(...$parameters));
    }

    public function delegatedMethods()
    {
        return [
            ['name',    [],      'table_'],
            ['column',  ['col'], $this->createMock(ColumnInterface::class)],
            ['columns', [],      []],
            ['indexes', [],      new IndexSet([])],
            ['options', [],      []],
            ['option',  ['foo'], 'bar'],
        ];
    }
}
