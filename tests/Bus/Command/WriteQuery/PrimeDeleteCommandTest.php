<?php

namespace Bus\Command\WriteQuery;

use Bdf\Prime\Bus\Command\PrimeCommandBus;
use Bdf\Prime\Bus\Command\WriteQuery\PrimeDeleteCommand;
use Bdf\Prime\Customer;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

class PrimeDeleteCommandTest extends TestCase
{
    use PrimeTestCase;

    private PrimeCommandBus $bus;

    protected function setUp(): void
    {
        $this->configurePrime();
        User::repository()->schema()->migrate();

        $this->bus = new PrimeCommandBus($this->prime());
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
        unset($this->bus);
    }

    public function test()
    {
        $user1 = new User([
            'id' => 42,
            'name' => 'John',
            'roles' => ['user'],
            'customer' => new Customer(['id' => 21]),
        ]);
        $user2 = new User([
            'id' => 66,
            'name' => 'Robert',
            'roles' => ['user'],
            'customer' => new Customer(['id' => 21]),
        ]);
        $user1->insert();
        $user2->insert();

        $dto = new #[PrimeDeleteCommand(User::class)] class {
            #[Criterion]
            public string $name = 'John';
        };

        $this->bus->execute($dto);
        $this->assertNull(User::refresh($user1));
        $this->assertEquals($user2, User::refresh($user2));
    }
}
