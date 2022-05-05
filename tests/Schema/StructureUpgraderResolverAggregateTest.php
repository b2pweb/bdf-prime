<?php

namespace Schema;

use Bdf\Prime\Schema\StructureUpgraderInterface;
use Bdf\Prime\Schema\StructureUpgraderResolverAggregate;
use Bdf\Prime\Schema\StructureUpgraderResolverInterface;
use Bdf\Prime\TestEntity;
use Bdf\Prime\TestEntityMapper;
use PHPUnit\Framework\TestCase;

class StructureUpgraderResolverAggregateTest extends TestCase
{
    public function test_empty()
    {
        $resolver = new StructureUpgraderResolverAggregate();

        $this->assertNull($resolver->resolveByMapperClass(TestEntityMapper::class));
        $this->assertNull($resolver->resolveByDomainClass(TestEntity::class));
    }

    public function test_matching()
    {
        $r1 = $this->createMock(StructureUpgraderResolverInterface::class);
        $r2 = $this->createMock(StructureUpgraderResolverInterface::class);

        $upgrader = $this->createMock(StructureUpgraderInterface::class);

        $resolver = new StructureUpgraderResolverAggregate([$r1, $r2]);

        $r1->method('resolveByMapperClass')->with(TestEntityMapper::class, true)->willReturn(null);
        $r2->method('resolveByMapperClass')->with(TestEntityMapper::class, true)->willReturn($upgrader);

        $this->assertSame($upgrader, $resolver->resolveByMapperClass(TestEntityMapper::class, true));

        $r1->method('resolveByDomainClass')->with(TestEntity::class, true)->willReturn(null);
        $r2->method('resolveByDomainClass')->with(TestEntity::class, true)->willReturn($upgrader);

        $this->assertSame($upgrader, $resolver->resolveByDomainClass(TestEntity::class, true));
    }

    public function test_not_matching()
    {
        $r1 = $this->createMock(StructureUpgraderResolverInterface::class);
        $r2 = $this->createMock(StructureUpgraderResolverInterface::class);

        $resolver = new StructureUpgraderResolverAggregate([$r1, $r2]);

        $r1->method('resolveByMapperClass')->with(TestEntityMapper::class, true)->willReturn(null);
        $r2->method('resolveByMapperClass')->with(TestEntityMapper::class, true)->willReturn(null);

        $this->assertNull($resolver->resolveByMapperClass(TestEntityMapper::class, true));

        $r1->method('resolveByDomainClass')->with(TestEntity::class, true)->willReturn(null);
        $r2->method('resolveByDomainClass')->with(TestEntity::class, true)->willReturn(null);

        $this->assertNull($resolver->resolveByDomainClass(TestEntity::class, true));
    }
}
