<?php

namespace Record;

use Bdf\Prime\Customer;
use Bdf\Prime\PrimeTestCase;
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
}
