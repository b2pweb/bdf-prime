<?php

namespace Bus\Query;

use Bdf\Prime\Bus\Query\PrimeQueryBus;
use Bdf\Prime\Customer;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\User;
use Bus\Fixtures\DbalSearchQuery;
use Bus\Fixtures\NameOfReturnType;
use Bus\Fixtures\RelationOtherConnectionQuery;
use Bus\Fixtures\SearchNameQuery;
use Bus\Fixtures\SearchUsersOfCustomerQuery;
use Bus\Fixtures\SearchUsersOnOtherConnectionQuery;
use Bus\Fixtures\SearchUsersQuery;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PrimeQueryBusTest extends TestCase
{
    use PrimeTestCase;

    private PrimeQueryBus $bus;
    private TestPack $testPack;

    protected function setUp(): void
    {
        $this->primeStart();

        $this->prime()->connections()->declareConnection('other', 'sqlite::memory:');

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
        $this->assertSame('SELECT t0.* FROM user_ t0 WHERE t0.name_ LIKE ?', $this->bus->generateQuery(new SearchUsersQuery('Jo'))->toSql());

        $results = $this->bus->query(new SearchUsersQuery('Jo', 2));
        $this->assertCount(1, $results);
        $this->assertEquals(3, $results[0]->id);
        $this->assertEquals('Joan Paul', $results[0]->name);
        $this->assertSame('SELECT t0.* FROM user_ t0 WHERE t0.name_ LIKE ? AND t0.customer_id LIKE ?', $this->bus->generateQuery(new SearchUsersQuery('Jo', 2))->toSql());
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
        $this->assertSame('SELECT * FROM user_ WHERE id_ = ?', $this->bus->generateQuery(new DbalSearchQuery(2))->toSql());

        $this->assertNull($this->bus->query(new DbalSearchQuery(404)));
    }

    public function test_select_repository_using_return_type()
    {
        $this->assertEquals(new User(['name' => 'John Doe']), $this->bus->query(new SearchNameQuery('Jo'), User::class));
        $this->assertSame('SELECT t0.name_ FROM user_ t0 WHERE t0.name_ LIKE ?', $this->bus->generateQuery(new SearchNameQuery('Jo'), User::class)->toSql());
        $this->assertEquals(new Customer(['name' => 'John Inc']), $this->bus->query(new SearchNameQuery('Jo'), Customer::class));
        $this->assertSame('SELECT t0.name_ FROM customer_ t0 WHERE t0.name_ LIKE ?', $this->bus->generateQuery(new SearchNameQuery('Jo'), Customer::class)->toSql());
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
        $this->assertSame('SELECT t0.* FROM user_ t0 WHERE t0.customer_id = ?', $this->bus->generateQuery(new SearchUsersOfCustomerQuery($this->testPack->get('customer1')))->toSql());

        $results = $this->bus->query(new SearchUsersOfCustomerQuery($this->testPack->get('customer1'), 'Jo'));
        $this->assertCount(1, $results);
        $this->assertContainsOnly(User::class, $results);
        $this->assertEquals(1, $results[0]->id);
        $this->assertEquals('John Doe', $results[0]->name);
        $this->assertSame('SELECT t0.* FROM user_ t0 WHERE t0.customer_id = ? AND (t0.name_ LIKE ?)', $this->bus->generateQuery(new SearchUsersOfCustomerQuery($this->testPack->get('customer1'), 'Jo'))->toSql());
    }

    public function test_query_other_connection()
    {
        $this->prime()->connection('other')->executeStatement(<<<'SQL'
            CREATE TABLE user_ (
                id_ INTEGER PRIMARY KEY,
                name_ TEXT NOT NULL,
                roles_ TEXT NOT NULL,
                customer_id INTEGER,
                faction_id INTEGER
            )
            SQL
        );

        $user = new User([
            'id' => 42,
            'name' => 'John Smith',
            'roles' => ['admin'],
        ]);
        User::repository()->on('other', fn (EntityRepository $repository) => $repository->insert($user));

        $results = $this->bus->query(new SearchUsersOnOtherConnectionQuery('John'));

        $this->assertCount(1, $results);
        $this->assertEquals(42, $results[0]->id);
        $this->assertEquals('John Smith', $results[0]->name);
        $this->assertEquals(['admin'], $results[0]->roles);
        $this->assertSame('SELECT t0.* FROM user_ t0 WHERE t0.name_ LIKE ?', $this->bus->generateQuery(new SearchUsersOnOtherConnectionQuery('John'))->toSql());
    }

    public function test_relation_other_connection()
    {
        $this->prime()->connection('other')->executeStatement(<<<'SQL'
            CREATE TABLE user_ (
                id_ INTEGER PRIMARY KEY,
                name_ TEXT NOT NULL,
                roles_ TEXT NOT NULL,
                customer_id INTEGER,
                faction_id INTEGER
            )
            SQL
        );

        $user = new User([
            'id' => 42,
            'name' => 'John Smith',
            'roles' => ['admin'],
            'customer' => $this->testPack->get('customer1'),
        ]);
        User::repository()->on('other', fn (EntityRepository $repository) => $repository->insert($user));

        $results = $this->bus->query(new RelationOtherConnectionQuery($this->testPack->get('customer1')));

        $this->assertCount(1, $results);
        $this->assertEquals(42, $results[0]->id);
        $this->assertEquals('John Smith', $results[0]->name);
        $this->assertEquals(['admin'], $results[0]->roles);
        $this->assertSame('SELECT t0.* FROM user_ t0 WHERE t0.customer_id = ?', $this->bus->generateQuery(new RelationOtherConnectionQuery($this->testPack->get('customer1')))->toSql());
    }

    public function test_class_not_annotated()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The query ArrayObject must be annotated with #[PrimeQuery]');
        $this->bus->query(new \ArrayObject());
    }

    public function test_missing_source()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The repository or connection cannot be resolved for the execution of Bus\Fixtures\SearchNameQuery.');
        $this->bus->query(new SearchNameQuery('Jo'));
    }
}
