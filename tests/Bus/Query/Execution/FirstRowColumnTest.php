<?php

namespace Bus\Query\Execution;

use Bdf\Prime\Bus\Query\Execution\FirstRowColumn;
use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Bus\Query\PrimeQueryBus;
use Bdf\Prime\Customer;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

class FirstRowColumnTest extends TestCase
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

    public function test()
    {
        $this->pack()->nonPersist([
            new User([
                'id' => 1,
                'name' => 'John Doe',
                'customer' => new Customer(['id' => 1]),
                'roles' => ['admin', 'user'],
            ]),
        ]);

        $dto = new #[PrimeQuery(User::class, method: new FirstRowColumn('name'))] class {};
        $this->assertSame('John Doe', $this->bus->query($dto));
    }
}
