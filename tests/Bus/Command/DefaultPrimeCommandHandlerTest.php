<?php

namespace Bus\Command;

use ArrayObject;
use Bdf\Prime\Bus\Command\DefaultPrimeCommandHandler;
use Bdf\Prime\Bus\Command\SetValue;
use Bdf\Prime\Bus\Command\TransactionManager;
use Bdf\Prime\Bus\Command\WriteMethod\Insert;
use Bdf\Prime\Bus\Command\WriteQuery\PrimeUpdateCommand;
use Bdf\Prime\Customer;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\Query\Criteria\StartsWithCriterion;
use Bdf\Prime\Query\Expression\Attribute;
use Bdf\Prime\Query\Expression\Json\JsonInsert;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

class DefaultPrimeCommandHandlerTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->configurePrime();
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_criteria()
    {
        $dto = new class {};
        $handler = new DefaultPrimeCommandHandler($dto::class);
        $this->assertSame([], iterator_to_array($handler->criteria($dto)));

        $dto = new class {
            #[Criterion]
            public string $foo = 'bar';
        };
        $handler = new DefaultPrimeCommandHandler($dto::class);
        $this->assertSame(['foo' => 'bar'], iterator_to_array($handler->criteria($dto)));

        $dto = new class {
            #[Criterion(operator: '>')]
            public string $foo = 'bar';

            #[StartsWithCriterion]
            public string $baz = 'qux';
        };
        $handler = new DefaultPrimeCommandHandler($dto::class);
        $this->assertEquals([
            'foo >' => 'bar',
            'baz' => (new Like('qux'))->escape()->startsWith(),
        ], iterator_to_array($handler->criteria($dto)));
    }

    public function test_values()
    {
        $dto = new #[PrimeUpdateCommand(User::class)] class {};
        $handler = new DefaultPrimeCommandHandler($dto::class);
        $this->assertSame([], $handler->values($dto));

        $dto = new #[PrimeUpdateCommand(User::class)] class {
            #[SetValue]
            public string $foo = 'bar';
        };
        $handler = new DefaultPrimeCommandHandler($dto::class);
        $this->assertSame(['foo' => 'bar'], $handler->values($dto));

        $dto = new #[PrimeUpdateCommand(User::class)] class {
            #[SetValue('aaa')]
            public string $foo = 'bar';

            #[SetValue('values', transformer: [DefaultPrimeCommandHandlerTest::class, 'json_insert'])]
            public string $value = 'abcd';
        };
        $handler = new DefaultPrimeCommandHandler($dto::class);
        $this->assertEquals([
            'aaa' => 'bar',
            'values' => new JsonInsert(new Attribute('values'), '$[#]', 'abcd'),
        ], $handler->values($dto));

        $dto = new #[PrimeUpdateCommand(User::class)] class {
            #[SetValue]
            public ?string $foo = null;

            #[SetValue(skipNull: true)]
            public ?string $bar = null;
        };
        $handler = new DefaultPrimeCommandHandler($dto::class);
        $this->assertSame(['foo' => null], $handler->values($dto));
    }

    public function test_call_simple()
    {
        User::repository()->schema()->migrate();

        $transaction = new TransactionManager();
        $dto = new class {
            #[Insert]
            public User $user;
        };
        $dto->user = new User([
            'id' => 42,
            'name' => 'foo',
            'roles' => ['user'],
            'customer' => new Customer(['id' => 4]),
        ]);
        $handler = new DefaultPrimeCommandHandler($dto::class);

        $handler($this->prime(), $dto, $transaction);
        $transaction->rollback();
        $this->assertNull(User::refresh($dto->user));

        $handler($this->prime(), $dto, $transaction);
        $transaction->commit();
        $this->assertEquals($dto->user, User::refresh($dto->user));
    }

    public function test_call_multiple_repositories()
    {
        User::repository()->schema()->migrate();
        Customer::repository()->schema()->migrate();

        $transaction = new TransactionManager();
        $dto = new class {
            #[Insert]
            public Customer $customer;

            #[Insert]
            public User $user;
        };
        $dto->customer = new Customer([
            'id' => 4,
            'name' => 'aaa',
        ]);
        $dto->user = new User([
            'id' => 42,
            'name' => 'foo',
            'roles' => ['user'],
            'customer' => new Customer(['id' => 4]),
        ]);
        $handler = new DefaultPrimeCommandHandler($dto::class);

        $handler($this->prime(), $dto, $transaction);
        $transaction->rollback();
        $this->assertNull(Customer::refresh($dto->customer));
        $this->assertNull(User::refresh($dto->user));

        $handler($this->prime(), $dto, $transaction);
        $transaction->commit();
        $this->assertEquals($dto->user, User::refresh($dto->user));
        $this->assertEquals($dto->customer, Customer::refresh($dto->customer));
    }

    public function test_call_should_skip_null_write()
    {
        User::repository()->schema()->migrate();
        Customer::repository()->schema()->migrate();

        $transaction = new TransactionManager();
        $dto = new class {
            #[Insert]
            public User $user;
        };
        $handler = new DefaultPrimeCommandHandler($dto::class);

        $handler($this->prime(), $dto, $transaction);
        $transaction->rollback();
        $this->assertSame(0, User::count());

        $handler($this->prime(), $dto, $transaction);
        $transaction->commit();
        $this->assertSame(0, User::count());
    }

    public function test_invalid_write_entity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The entity ArrayObject is not managed by Prime');

        $transaction = new TransactionManager();
        $dto = new class {
            #[Insert]
            public ArrayObject $user;
        };
        $dto->user = new ArrayObject();
        $handler = new DefaultPrimeCommandHandler($dto::class);

        $handler($this->prime(), $dto, $transaction);
    }

    public function test_invalid_query_write_entity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The entity ArrayObject is not managed by Prime');

        $transaction = new TransactionManager();
        $dto = new #[PrimeUpdateCommand(ArrayObject::class)] class {};
        $handler = new DefaultPrimeCommandHandler($dto::class);

        $handler($this->prime(), $dto, $transaction);
    }

    public function test_call_write_query()
    {
        User::repository()->schema()->migrate();

        $user = new User([
            'id' => 42,
            'name' => 'foo',
            'roles' => ['user'],
            'customer' => new Customer(['id' => 4]),
        ]);
        $user->insert();

        $transaction = new TransactionManager();
        $dto = new #[PrimeUpdateCommand(User::class)] class {
            #[Criterion]
            public int $id;

            #[SetValue]
            public array $roles;
        };
        $dto->id = $user->id;
        $dto->roles = ['admin'];
        $handler = new DefaultPrimeCommandHandler($dto::class);

        $handler($this->prime(), $dto, $transaction);
        $transaction->rollback();
        $this->assertSame(['user'], User::refresh($user)->roles);

        $handler($this->prime(), $dto, $transaction);
        $transaction->commit();
        $this->assertSame(['admin'], User::refresh($user)->roles);
    }

    public static function json_insert(string $value): JsonInsert
    {
        return new JsonInsert(new Attribute('values'), '$[#]', $value);
    }
}
