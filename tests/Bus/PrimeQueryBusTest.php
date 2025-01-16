<?php

namespace Bus;

require_once __DIR__.'/Fixtures/SearchUsersQuery.php';
require_once __DIR__.'/Fixtures/DbalSearchQuery.php';
require_once __DIR__.'/Fixtures/SearchNameQuery.php';
require_once __DIR__.'/Fixtures/NameOfReturnType.php';
require_once __DIR__.'/Fixtures/SearchUsersOfCustomerQuery.php';

use Bdf\Prime\Bus\PrimeQueryBus;
use Bdf\Prime\Customer;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\User;
use Bus\Fixtures\DbalSearchQuery;
use Bus\Fixtures\NameOfReturnType;
use Bus\Fixtures\SearchNameQuery;
use Bus\Fixtures\SearchUsersOfCustomerQuery;
use Bus\Fixtures\SearchUsersQuery;
use PHPUnit\Framework\TestCase;

class PrimeQueryBusTest extends TestCase
{
    use PrimeTestCase;

    private PrimeQueryBus $bus;
    private TestPack $testPack;

    protected function setUp(): void
    {
        $this->primeStart();

        $this->bus = new PrimeQueryBus($this->prime());
        $this->testPack = $this->pack();
    }

    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
        unset($this->bus);
        unset($this->testPack);
    }

    public function declareTestData(TestPack $testPack)
    {
        $testPack->persist([
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
    }

    public function test_simple_query()
    {
        $results = $this->bus->query(new SearchUsersQuery('Jo'));
        $this->assertCount(2, $results);
        $this->assertEquals(1, $results[0]->id);
        $this->assertEquals('John Doe', $results[0]->name);
        $this->assertEquals(3, $results[1]->id);
        $this->assertEquals('Joan Paul', $results[1]->name);

        $results = $this->bus->query(new SearchUsersQuery('Jo', 2));
        $this->assertCount(1, $results);
        $this->assertEquals(3, $results[0]->id);
        $this->assertEquals('Joan Paul', $results[0]->name);
    }

    public function test_dbal_query()
    {
        $this->assertSame([
            'id_' => 2,
            'name_' => 'Jane Smith',
            'roles_' => ',user,',
            'customer_id' => 1,
            'faction_id' => null,
        ], $this->bus->query(new DbalSearchQuery(2)));

        $this->assertNull($this->bus->query(new DbalSearchQuery(404)));
    }

    public function test_select_repository_using_return_type()
    {
        $this->assertEquals(new User(['name' => 'John Doe']), $this->bus->query(new SearchNameQuery('Jo'), User::class));
        $this->assertEquals(new Customer(['name' => 'John Inc']), $this->bus->query(new SearchNameQuery('Jo'), Customer::class));
        $this->assertSame('John Doe', $this->bus->query(new SearchNameQuery('Jo'), new NameOfReturnType(User::class)));
        $this->assertSame('John Inc', $this->bus->query(new SearchNameQuery('Jo'), new NameOfReturnType(Customer::class)));
    }

    public function test_with_relation_query()
    {
        $results = $this->bus->query(new SearchUsersOfCustomerQuery($this->testPack->get('customer1')));
        $this->assertCount(2, $results);
        $this->assertContainsOnly(User::class, $results);
        $this->assertEquals(1, $results[0]->id);
        $this->assertEquals('John Doe', $results[0]->name);
        $this->assertEquals(2, $results[1]->id);
        $this->assertEquals('Jane Smith', $results[1]->name);

        $results = $this->bus->query(new SearchUsersOfCustomerQuery($this->testPack->get('customer1'), 'Jo'));
        $this->assertCount(1, $results);
        $this->assertContainsOnly(User::class, $results);
        $this->assertEquals(1, $results[0]->id);
        $this->assertEquals('John Doe', $results[0]->name);
    }
}
