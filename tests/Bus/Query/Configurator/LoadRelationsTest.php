<?php

namespace Bus\Query\Configurator;

use Bdf\Prime\Bus\Query\Configurator\LoadRelations;
use Bdf\Prime\Bus\Query\Execution\QueryExecutionMethod;
use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Bus\Query\PrimeQueryBus;
use Bdf\Prime\Customer;
use Bdf\Prime\Faction;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

use function array_map;

class LoadRelationsTest extends TestCase
{
    use PrimeTestCase;

    private PrimeQueryBus $bus;

    protected function setUp(): void
    {
        $this->primeStart();
        $this->bus = new PrimeQueryBus($this->prime());
    }

    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
        unset($this->bus);
    }

    public function test_single_relation()
    {
        $this->pack()->nonPersist([
            'customer1' => $customer1 = new Customer([
                'id' => 1,
                'name' => 'John Inc',
            ]),
            'customer2' => $customer2 = new Customer([
                'id' => 2,
                'name' => 'Mrkl',
            ]),
            'user1' => new User([
                'id' => 1,
                'name' => 'John Doe',
                'customer' => $customer1,
                'roles' => ['admin', 'user'],
            ]),
            'user2' => new User([
                'id' => 2,
                'name' => 'Jane Smith',
                'customer' => $customer1,
                'roles' => ['user'],
            ]),
            'user3' => new User([
                'id' => 3,
                'name' => 'Joan Paul',
                'customer' => $customer2,
                'roles' => ['admin'],
            ]),
        ]);

        $dto = new #[PrimeQuery(User::class), LoadRelations('customer')] class {};
        $results = $this->bus->query($dto);

        $customers = array_map(fn(User $user) => $user->customer, $results);
        $this->assertEquals([$customer1, $customer1, $customer2], $customers);
    }

    public function test_use_class_name()
    {
        $this->pack()->nonPersist([
            'customer1' => $customer1 = new Customer([
                'id' => 1,
                'name' => 'John Inc',
            ]),
            'customer2' => $customer2 = new Customer([
                'id' => 2,
                'name' => 'Mrkl',
            ]),
            'user1' => new User([
                'id' => 1,
                'name' => 'John Doe',
                'customer' => $customer1,
                'roles' => ['admin', 'user'],
            ]),
            'user2' => new User([
                'id' => 2,
                'name' => 'Jane Smith',
                'customer' => $customer1,
                'roles' => ['user'],
            ]),
            'user3' => new User([
                'id' => 3,
                'name' => 'Joan Paul',
                'customer' => $customer2,
                'roles' => ['admin'],
            ]),
        ]);

        $dto = new #[PrimeQuery(User::class), LoadRelations(Customer::class)] class {};
        $results = $this->bus->query($dto);

        $customers = array_map(fn(User $user) => $user->customer, $results);
        $this->assertEquals([$customer1, $customer1, $customer2], $customers);
    }

    public function test_multiple_relations()
    {
        $this->pack()->nonPersist([
            $customer1 = new Customer([
                'id' => 1,
                'name' => 'John Inc',
            ]),
            $faction = new Faction([
                'id' => 1,
                'name' => 'Faction 1',
                'domain' => 'user',
            ]),
            new User([
                'id' => 1,
                'name' => 'John Doe',
                'customer' => $customer1,
                'faction' => $faction,
                'roles' => ['admin', 'user'],
            ]),
        ]);

        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::First), LoadRelations('customer', 'faction')] class {};
        $result = $this->bus->query($dto);

        $this->assertEquals($customer1, $result->customer);
        $this->assertEquals($faction, $result->faction);
    }
}
