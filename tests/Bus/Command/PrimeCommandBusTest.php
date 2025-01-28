<?php

namespace Bus\Command;

use Bdf\Prime\Bus\Command\PrimeCommandBus;
use Bdf\Prime\Customer;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\ServiceLocator;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\User;
use Bus\Fixtures\CreateUserAndCustomerCommand;
use Bus\Fixtures\ManualDeleteCommand;
use Bus\Fixtures\ManualUpdateCommand;
use Bus\Fixtures\SimpleDeleteCommand;
use Bus\Fixtures\SimpleInsertCommand;
use Bus\Fixtures\SimpleUpdateCommand;
use Bus\Fixtures\UpdateWithExpressionCommand;
use PHPUnit\Framework\TestCase;

class PrimeCommandBusTest extends TestCase
{
    use PrimeTestCase;

    private PrimeCommandBus $bus;

    protected function setUp(): void
    {
        $this->primeStart();

        $this->bus = new PrimeCommandBus($this->prime(), []);
    }

    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
        unset($this->bus);
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

    public function test_insert()
    {
        $newUser = new User([
            'id' => 4,
            'name' => 'Mark Amstrong',
            'customer' => new Customer(['id' => 1]),
            'roles' => ['user'],
        ]);

        $this->bus->execute(new SimpleInsertCommand($newUser));
        $this->assertEquals($newUser, User::refresh($newUser));
    }

    public function test_update()
    {
        $newUser = new User([
            'id' => 4,
            'name' => 'Mark Amstrong',
            'customer' => new Customer(['id' => 1]),
            'roles' => ['user'],
        ]);
        $newUser->insert();

        $newUser->roles = ['admin'];

        $this->bus->execute(new SimpleUpdateCommand($newUser));
        $this->assertEquals($newUser, User::refresh($newUser));
    }

    public function test_delete()
    {
        $newUser = new User([
            'id' => 4,
            'name' => 'Mark Amstrong',
            'customer' => new Customer(['id' => 1]),
            'roles' => ['user'],
        ]);
        $newUser->insert();

        $this->bus->execute(new SimpleDeleteCommand($newUser));
        $this->assertNull(User::refresh($newUser));
    }

    public function test_multiple_commands()
    {
        $user1 = new User([
            'id' => 4,
            'name' => 'Mark Amstrong',
            'customer' => new Customer(['id' => 1]),
            'roles' => ['user'],
        ]);
        $user2 = new User([
            'id' => 5,
            'name' => 'Tommy Lee',
            'customer' => new Customer(['id' => 1]),
            'roles' => ['user'],
        ]);
        $user2->insert();
        $user3 = new User([
            'id' => 6,
            'name' => 'Alice Cooper',
            'customer' => new Customer(['id' => 1]),
            'roles' => ['user'],
        ]);
        $user3->insert();

        $user2->roles = ['admin'];

        $this->bus->execute(
            new SimpleInsertCommand($user1),
            new SimpleUpdateCommand($user2),
            new SimpleDeleteCommand($user3),
        );

        $this->assertEquals($user1, User::refresh($user1));
        $this->assertEquals($user2, User::refresh($user2));
        $this->assertNull(User::refresh($user3));
    }

    public function test_multiple_commands_with_error_should_rollback()
    {
        $user1 = new User([
            'id' => 2,
            'name' => 'Mark Amstrong',
            'customer' => new Customer(['id' => 1]),
            'roles' => ['user'],
        ]);
        $user2 = new User([
            'id' => 5,
            'name' => 'Tommy Lee',
            'customer' => new Customer(['id' => 1]),
            'roles' => ['user'],
        ]);
        $user2->insert();
        $user3 = new User([
            'id' => 6,
            'name' => 'Alice Cooper',
            'customer' => new Customer(['id' => 1]),
            'roles' => ['user'],
        ]);
        $user3->insert();

        $user2->roles = ['admin'];

        try {
            $this->bus->execute(
                new SimpleUpdateCommand($user2),
                new SimpleDeleteCommand($user3),
                new SimpleInsertCommand($user1),
            );
        } catch (PrimeException $e) {
            $this->assertStringContainsString('Integrity constraint violation', $e->getMessage());
        }

        $this->assertNotEquals($user1, User::refresh($user1));
        $this->assertEquals(['user'], User::refresh($user2)->roles);
        $this->assertEquals($user3, User::refresh($user3));
    }

    public function test_multiple_write_one_command()
    {
        $customer = new Customer([
            'id' => 3,
            'name' => 'Aztro',
        ]);
        $user = new User([
            'id' => 5,
            'name' => 'Paul',
            'customer' => $customer,
            'roles' => ['admin', 'user'],
        ]);

        $this->bus->execute(new CreateUserAndCustomerCommand($customer, $user));

        $expectedUser = clone $user;
        $expectedUser->customer = new Customer(['id' => 3]);

        $this->assertEquals($customer, Customer::refresh($customer));
        $this->assertEquals($expectedUser, User::refresh($user));
    }

    public function test_multiple_write_one_command_error_should_rollback()
    {
        $customer = new Customer([
            'id' => 3,
            'name' => 'Aztro',
        ]);
        $user = new User([
            'id' => 1,
            'name' => 'Paul',
            'customer' => $customer,
            'roles' => ['admin', 'user'],
        ]);

        try {
            $this->bus->execute(new CreateUserAndCustomerCommand($customer, $user));
        } catch (PrimeException $e) {
            $this->assertStringContainsString('Integrity constraint violation', $e->getMessage());
        }

        $this->assertNull(Customer::refresh($customer));
        $this->assertNotEquals('Paul', User::refresh($user)->name);
    }

    public function test_update_query()
    {
        $this->pack()->nonPersist([
            $customer = new Customer([
                'id' => 3,
                'name' => 'Aztro',
            ]),
            $user1 = new User([
                'id' => 4,
                'name' => 'Paul',
                'customer' => $customer,
                'roles' => [],
            ]),
            $user2 = new User([
                'id' => 5,
                'name' => 'John',
                'customer' => $customer,
                'roles' => [],
            ]),
        ]);

        $this->bus->execute(new ManualUpdateCommand($customer->id, ['new-role']));

        $this->assertEquals(['new-role'], User::refresh($user1)->roles);
        $this->assertEquals(['new-role'], User::refresh($user2)->roles);
        $this->assertEquals($this->pack()->get('user1')->roles, User::refresh($this->pack()->get('user1'))->roles);
        $this->assertEquals($this->pack()->get('user2')->roles, User::refresh($this->pack()->get('user2'))->roles);
        $this->assertEquals($this->pack()->get('user3')->roles, User::refresh($this->pack()->get('user3'))->roles);
    }

    public function test_delete_query()
    {
        $this->pack()->nonPersist([
            $customer = new Customer([
                'id' => 3,
                'name' => 'Aztro',
            ]),
            $user1 = new User([
                'id' => 4,
                'name' => 'Paul',
                'customer' => $customer,
                'roles' => [],
            ]),
            $user2 = new User([
                'id' => 5,
                'name' => 'John',
                'customer' => $customer,
                'roles' => [],
            ]),
        ]);

        $this->bus->execute(new ManualDeleteCommand($customer->id));

        $this->assertNull(User::refresh($user1));
        $this->assertNull(User::refresh($user2));
        $this->assertEquals($this->pack()->get('user1')->roles, User::refresh($this->pack()->get('user1'))->roles);
        $this->assertEquals($this->pack()->get('user2')->roles, User::refresh($this->pack()->get('user2'))->roles);
        $this->assertEquals($this->pack()->get('user3')->roles, User::refresh($this->pack()->get('user3'))->roles);
    }

    public function test_update_with_expression_query()
    {
        $this->pack()->nonPersist([
            $customer = new Customer([
                'id' => 3,
                'name' => 'Aztro',
            ]),
            $user1 = new User([
                'id' => 4,
                'name' => 'Paul',
                'customer' => $customer,
                'roles' => ['user'],
            ]),
            $user2 = new User([
                'id' => 5,
                'name' => 'John',
                'customer' => $customer,
                'roles' => ['admin'],
            ]),
        ]);

        $this->bus->execute(new UpdateWithExpressionCommand($customer->id, ['new-role']));

        $this->assertEquals(['user', 'new-role'], User::refresh($user1)->roles);
        $this->assertEquals(['admin', 'new-role'], User::refresh($user2)->roles);
        $this->assertEquals($this->pack()->get('user1')->roles, User::refresh($this->pack()->get('user1'))->roles);
        $this->assertEquals($this->pack()->get('user2')->roles, User::refresh($this->pack()->get('user2'))->roles);
        $this->assertEquals($this->pack()->get('user3')->roles, User::refresh($this->pack()->get('user3'))->roles);
    }

    public function test_custom_handler()
    {
        $this->prime()->connection('test')->executeStatement('CREATE TABLE data (id INTEGER PRIMARY KEY AUTOINCREMENT, json TEXT)');

        $bus = new PrimeCommandBus($this->prime(), [
            \stdClass::class => function (ServiceLocator $prime, object $command) {
                $prime->connection('test')->insert('data', ['json' => json_encode($command)]);
            },
        ]);

        $bus->execute((object) [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertEquals([
            [
                'id' => 1,
                'json' => '{"foo":"bar","baz":"qux"}',
            ]
        ], $this->prime()->connection('test')->from('data')->all());
    }
}
