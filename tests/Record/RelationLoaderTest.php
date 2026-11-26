<?php

namespace Record;

use Bdf\Prime\Customer;
use Bdf\Prime\CustomerPack;
use Bdf\Prime\Pack;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Record\Field;
use Bdf\Prime\Record\RelationLoader;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

class RelationLoaderTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->primeStart();
    }

    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }

    public function test_load_simple_relation()
    {
        $this->pack()->nonPersist([
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

        $loader = new RelationLoader(
            'customer',
            'c',
            'customerId',
            'customer_id'
        );

        $rows = [
            ['id' => 1, 'customer_id' => 1],
            ['id' => 2, 'customer_id' => 2],
            ['id' => 3, 'customer_id' => 1],
            ['id' => 4, 'customer_id' => 404],
        ];

        $loaded = $loader->load($this->prime()->repository(User::class), $rows);

        $this->assertEquals([
            ['id' => 1, 'customer_id' => 1, 'c' => $customer1],
            ['id' => 2, 'customer_id' => 2, 'c' => $customer2],
            ['id' => 3, 'customer_id' => 1, 'c' => $customer1],
            ['id' => 4, 'customer_id' => 404, 'c' => null],
        ], $loaded);
    }

    public function test_load_many_relation()
    {
        $this->pack()->nonPersist([
            $customer1 = new Customer([
                'id' => 1,
                'name' => 'John Inc.',
            ]),
            $customer2 = new Customer([
                'id' => 2,
                'name' => 'Doe Ltd.',
            ]),
            $user1 = new User([
                'id' => 1,
                'name' => 'John Miller',
                'customer' => new Customer(['id' => 1]),
                'roles' => ['admin', 'user'],
            ]),
            $user2 = new User([
                'id' => 2,
                'name' => 'Jane Doe',
                'customer' => new Customer(['id' => 2]),
                'roles' => ['admin', 'user'],
            ]),
            $user3 = new User([
                'id' => 3,
                'name' => 'Mickey Mouse',
                'customer' => new Customer(['id' => 1]),
                'roles' => ['user'],
            ]),
        ]);

        $loader = new RelationLoader(
            'users',
            'u',
            'id',
            'id'
        );

        $rows = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 404],
        ];

        $loaded = $loader->load($this->prime()->repository(Customer::class), $rows);

        $this->assertEquals([
            ['id' => 1, 'u' => [$user1, $user3]],
            ['id' => 2, 'u' => [$user2]],
            ['id' => 404, 'u' => []],
        ], $loaded);
    }

    public function test_load_simple_relation_as_record()
    {
        $this->declareUsers();

        $loader = new RelationLoader(
            'customer',
            'c',
            'customerId',
            'customer_id',
            CustomerRecord::class
        );

        $rows = [
            ['id' => 1, 'customer_id' => 1],
            ['id' => 2, 'customer_id' => 2],
            ['id' => 3, 'customer_id' => 1],
            ['id' => 4, 'customer_id' => 404],
        ];

        $loaded = $loader->load($this->prime()->repository(User::class), $rows);

        $this->assertEquals([
            ['id' => 1, 'customer_id' => 1, 'c' => new CustomerRecord(1, 'John Inc.')],
            ['id' => 2, 'customer_id' => 2, 'c' => new CustomerRecord(2, 'Doe Ltd.')],
            ['id' => 3, 'customer_id' => 1, 'c' => new CustomerRecord(1, 'John Inc.')],
            ['id' => 4, 'customer_id' => 404, 'c' => null],
        ], $loaded);
    }

    public function test_load_simple_relation_as_record_with_field_mapping()
    {
        $this->declareUsers();

        $loader = new RelationLoader(
            'customer',
            'c',
            'customerId',
            'customer_id',
            CustomerRecordWithMapping::class
        );

        $rows = [
            ['id' => 1, 'customer_id' => 1],
        ];

        $loaded = $loader->load($this->prime()->repository(User::class), $rows);

        $this->assertEquals([
            ['id' => 1, 'customer_id' => 1, 'c' => new CustomerRecordWithMapping('JOHN INC.')],
        ], $loaded);
    }

    public function test_load_many_relation_as_record()
    {
        $this->declareUsers();

        $loader = new RelationLoader(
            'users',
            'u',
            'id',
            'id',
            UserRecord::class
        );

        $rows = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 404],
        ];

        $loaded = $loader->load($this->prime()->repository(Customer::class), $rows);

        $this->assertEquals([
            ['id' => 1, 'u' => [new UserRecord(1, 'John Miller'), new UserRecord(3, 'Mickey Mouse')]],
            ['id' => 2, 'u' => [new UserRecord(2, 'Jane Doe')]],
            ['id' => 404, 'u' => []],
        ], $loaded);
    }

    public function test_load_belongsToMany_relation_as_record()
    {
        $this->pack()->nonPersist([
            new Customer(['id' => 1, 'name' => 'John Inc.']),
            new Customer(['id' => 2, 'name' => 'Doe Ltd.']),
            new Pack(['id' => 1, 'label' => 'Pack referencement']),
            new Pack(['id' => 2, 'label' => 'Pack classic']),
            new CustomerPack(['customerId' => 1, 'packId' => 1]),
            new CustomerPack(['customerId' => 1, 'packId' => 2]),
            new CustomerPack(['customerId' => 2, 'packId' => 2]),
        ]);

        $loader = new RelationLoader(
            'packs',
            'p',
            'id',
            'id',
            PackRecord::class
        );

        $rows = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 404],
        ];

        $loaded = $loader->load($this->prime()->repository(Customer::class), $rows);

        $this->assertEquals([
            ['id' => 1, 'p' => [new PackRecord(1, 'Pack referencement'), new PackRecord(2, 'Pack classic')]],
            ['id' => 2, 'p' => [new PackRecord(2, 'Pack classic')]],
            ['id' => 404, 'p' => []],
        ], $loaded);
    }

    public function test_load_as_record_without_foreign_key()
    {
        $this->declareUsers();

        $loader = new RelationLoader(
            'customer',
            'c',
            'customerId',
            'customer_id',
            CustomerRecord::class
        );

        $rows = [
            ['id' => 1, 'customer_id' => null],
            ['id' => 2],
        ];

        $loaded = $loader->load($this->prime()->repository(User::class), $rows);

        $this->assertEquals([
            ['id' => 1, 'customer_id' => null, 'c' => null],
            ['id' => 2, 'c' => null],
        ], $loaded);
    }

    public function test_load_as_record_with_empty_rows()
    {
        $this->declareUsers();

        $loader = new RelationLoader(
            'customer',
            'c',
            'customerId',
            'customer_id',
            CustomerRecord::class
        );

        $this->assertSame([], $loader->load($this->prime()->repository(User::class), []));
    }

    private function declareUsers(): void
    {
        $this->pack()->nonPersist([
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
}

final readonly class CustomerRecord
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}

final readonly class CustomerRecordWithMapping
{
    public function __construct(
        #[Field('name', transformer: 'strtoupper')]
        public string $label,
    ) {}
}

final readonly class UserRecord
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}

final readonly class PackRecord
{
    public function __construct(
        public int $id,
        public string $label,
    ) {}
}
