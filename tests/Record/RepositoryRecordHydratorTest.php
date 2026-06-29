<?php

namespace Bdf\Prime\Record;

use Bdf\Prime\Customer;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

class RepositoryRecordHydratorTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->primeStart();
    }

    public function declareTestData(TestPack $pack)
    {
        $pack->persist([
            $customer1 = new Customer([
                'id' => 1,
                'name' => 'John Inc.',
            ]),
            $customer2 = new Customer([
                'id' => 2,
                'name' => 'Doe Ltd.',
            ]),
            new User([
                'id' => 1,
                'name' => 'John Miller',
                'customer' => $customer1,
                'roles' => ['admin', 'user'],
            ]),
            new User([
                'id' => 2,
                'name' => 'Jane Doe',
                'customer' => $customer2,
                'roles' => ['admin', 'user'],
            ]),
            new User([
                'id' => 3,
                'name' => 'Mickey Mouse',
                'customer' => $customer1,
                'roles' => ['user'],
            ]),
        ]);
    }

    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }

    public function test_base_entity()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));

        $this->assertNull($hydrator->projection(User::class));

        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $this->assertSame($rows, $hydrator->prepare(User::class, $rows));
        $this->assertEquals(new User([
            'id' => 1,
            'name' => 'John Miller',
            'customer' => new Customer(['id' => 1]),
            'roles' => ['admin', 'user'],
        ]), $hydrator->instantiate(User::class, $rows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(User::class, $rows));
    }

    public function test_simple()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $this->assertSame(['id', 'name'], $hydrator->projection(SimpleOrmRecord::class));
        $this->assertSame($rows, $hydrator->prepare(SimpleOrmRecord::class, $rows));
        $this->assertEquals(new SimpleOrmRecord(1, 'John Miller'), $hydrator->instantiate(SimpleOrmRecord::class, $rows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(SimpleOrmRecord::class, $rows));
    }

    public function test_with_name_mapping()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $this->assertSame(['id', 'name'], $hydrator->projection(OrmRecordWithNameMapping::class));
        $this->assertSame($rows, $hydrator->prepare(OrmRecordWithNameMapping::class, $rows));
        $this->assertEquals(new OrmRecordWithNameMapping(1, 'John Miller'), $hydrator->instantiate(OrmRecordWithNameMapping::class, $rows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithNameMapping::class, $rows));
    }

    public function test_with_relation()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $expectedRows = $rows;
        $expectedRows[0]['customer'] = new Customer(['id' => 1, 'name' => 'John Inc.']);
        $expectedRows[1]['customer'] = new Customer(['id' => 2, 'name' => 'Doe Ltd.']);
        $expectedRows[2]['customer'] = new Customer(['id' => 1, 'name' => 'John Inc.']);

        $this->assertSame(['name', 'customer.id'], $hydrator->projection(OrmRecordWithRelation::class));
        $this->assertEquals($expectedRows, $hydrator->prepare(OrmRecordWithRelation::class, $rows));
        $this->assertEquals(new OrmRecordWithRelation('John Miller', new Customer(['id' => 1, 'name' => 'John Inc.'])), $hydrator->instantiate(OrmRecordWithRelation::class, $expectedRows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithRelation::class, $rows));
    }

    public function test_with_implicit_type()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $this->assertSame(['name', 'roles'], $hydrator->projection(OrmRecordWithImplicitType::class));
        $this->assertSame($rows, $hydrator->prepare(OrmRecordWithImplicitType::class, $rows));
        $this->assertEquals(new OrmRecordWithImplicitType('John Miller', ['admin', 'user']), $hydrator->instantiate(OrmRecordWithImplicitType::class, $rows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithImplicitType::class, $rows));
    }
}

class SimpleOrmRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

class OrmRecordWithNameMapping
{
    public function __construct(
        #[Field('id')]
        public readonly int $foo,
        #[Field('name')]
        public readonly string $bar,
    ) {}
}

class OrmRecordWithRelation
{
    public function __construct(
        public readonly string $name,
        #[LoadRelation(Customer::class)]
        public readonly Customer $customer,
    ) {}
}

class OrmRecordWithImplicitType
{
    public function __construct(
        public readonly string $name,
        public readonly array $roles,
    ) {}
}
