<?php

namespace Bdf\Prime\Schema\Adapter\MapperInfo;

use Bdf\Prime\Customer;
use Bdf\Prime\Mapper\Info\MapperInfo;
use Bdf\Prime\Mapper\Info\ObjectPropertyInfo;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Adapter\MapperInfo\Resolver\MapperInfoForeignKeyResolver;
use Bdf\Prime\Schema\Adapter\MapperInfo\Resolver\MapperInfoResolverInterface;
use Bdf\Prime\Schema\Constraint\ConstraintVisitorInterface;
use Bdf\Prime\Schema\Constraint\ForeignKey;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MapperInfoConstraintSetTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->primeStart();
    }

    /**
     *
     */
    public function test_load_constraints_unit()
    {
        $info = new MapperInfo(Customer::repository()->mapper());
        $resolver = $this->createMock(MapperInfoResolverInterface::class);

        $set = new MapperInfoConstraintSet($info, [$resolver]);

        $resolver->expects($this->exactly(7))
            ->method('fromRelation')
            ->with($info, $this->isInstanceOf(ObjectPropertyInfo::class))
            ->willReturn([])
        ;

        $this->assertEmpty($set->all());
    }

    /**
     *
     */
    public function test_functional()
    {
        $info = new MapperInfo(Customer::repository()->mapper());

        $set = new MapperInfoConstraintSet($info, [new MapperInfoForeignKeyResolver($this->prime())]);

        $this->assertCount(1, $set->all());
        $this->assertInstanceOf(ForeignKey::class, $set->get('parent'));

        $visitor = $this->createMock(ConstraintVisitorInterface::class);

        $visitor->expects($this->once())
            ->method('onForeignKey')
            ->with($set->get('parent'))
        ;

        $this->assertSame($set, $set->apply($visitor));
    }
}
