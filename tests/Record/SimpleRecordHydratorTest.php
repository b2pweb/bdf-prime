<?php

namespace Bdf\Prime\Record;

use Bdf\Prime\Platform\PlatformInterface;
use PHPUnit\Framework\TestCase;

class SimpleRecordHydratorTest extends TestCase
{

    public function test_simple()
    {
        $hydrator = new SimpleRecordHydrator();
        $rows = [
            ['id' => 1, 'name' => 'John Miller', 'customer_id' => 1, 'roles' => ',admin,user,'],
            ['id' => 2, 'name' => 'Jane Doe', 'customer_id' => 2, 'roles' => ',admin,user,'],
            ['id' => 3, 'name' => 'Mickey Mouse', 'customer_id' => 1, 'roles' => ',user,'],
        ];

        $this->assertSame(['id', 'name'], $hydrator->projection(SimpleDbalRecord::class));
        $this->assertSame($rows, $hydrator->prepare(SimpleDbalRecord::class, $rows));
        $this->assertEquals(new SimpleDbalRecord(1, 'John Miller'), $hydrator->instantiate(SimpleDbalRecord::class, $rows[0], $this->createMock(PlatformInterface::class)));
        $this->assertSame($rows, $hydrator->finalize(SimpleDbalRecord::class, $rows, $rows));
    }

    public function test_with_name_mapping()
    {
        $hydrator = new SimpleRecordHydrator();
        $rows = [
            ['id' => 1, 'name' => 'John Miller', 'customer_id' => 1, 'roles' => ',admin,user,'],
            ['id' => 2, 'name' => 'Jane Doe', 'customer_id' => 2, 'roles' => ',admin,user,'],
            ['id' => 3, 'name' => 'Mickey Mouse', 'customer_id' => 1, 'roles' => ',user,'],
        ];

        $this->assertSame(['id', 'name'], $hydrator->projection(DbalRecordWithNameMapping::class));
        $this->assertSame($rows, $hydrator->prepare(DbalRecordWithNameMapping::class, $rows));
        $this->assertEquals(new DbalRecordWithNameMapping(1, 'John Miller'), $hydrator->instantiate(DbalRecordWithNameMapping::class, $rows[0], $this->createMock(PlatformInterface::class)));
        $this->assertSame($rows, $hydrator->finalize(DbalRecordWithNameMapping::class, $rows, $rows));
    }

    public function test_with_embedded()
    {
        $hydrator = new SimpleRecordHydrator();
        $rows = [
            ['id' => 1, 'name' => 'John Miller', 'customer_id' => 1, 'customer_name' => 'John Inc.'],
            ['id' => 2, 'name' => 'Jane Doe', 'customer_id' => 2, 'customer_name' => 'Doe Ltd.'],
        ];

        $this->assertSame(['id', 'name', 'customer_id', 'customer_name'], $hydrator->projection(DbalRecordWithEmbedded::class));
        $this->assertSame($rows, $hydrator->prepare(DbalRecordWithEmbedded::class, $rows));
        $this->assertEquals(
            new DbalRecordWithEmbedded(1, 'John Miller', new DbalCustomerRecord(1, 'John Inc.')),
            $hydrator->instantiate(DbalRecordWithEmbedded::class, $rows[0], $this->createMock(PlatformInterface::class))
        );
        $this->assertSame($rows, $hydrator->finalize(DbalRecordWithEmbedded::class, $rows, $rows));
    }

    public function test_instance_should_be_shared()
    {
        $this->assertSame(SimpleRecordHydrator::instance(), SimpleRecordHydrator::instance());
    }
}

class SimpleDbalRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

class DbalRecordWithNameMapping
{
    public function __construct(
        #[Field('id')]
        public readonly int $foo,
        #[Field('name')]
        public readonly string $bar,
    ) {}
}

class DbalRecordWithEmbedded
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        #[Embedded]
        public readonly DbalCustomerRecord $customer,
    ) {}
}

class DbalCustomerRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
