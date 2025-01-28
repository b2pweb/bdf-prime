<?php

namespace Bus\Command\WriteMethod;

use Bdf\Prime\Bus\Command\PrimeCommandBus;
use Bdf\Prime\Bus\Command\WriteMethod\Delete;
use Bdf\Prime\Bus\Command\WriteMethod\Insert;
use Bdf\Prime\Bus\Command\WriteMethod\Save;
use Bdf\Prime\Customer;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

class SaveTest extends TestCase
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

    public function test_single_entity()
    {
        $user = new User([
            'id' => 42,
            'name' => 'John',
            'roles' => ['user'],
            'customer' => new Customer(['id' => 21]),
        ]);

        $dto = new class {
            #[Save]
            public User $user;
        };
        $dto->user = $user;

        $this->bus->execute($dto);
        $this->assertEquals($user, User::refresh($user));
    }

    public function test_bulk()
    {
        $users = [];

        for ($i = 0; $i < 10; ++$i) {
            $users[] = $user = new User([
                'id' => 42 + $i,
                'name' => 'John ' . $i,
                'roles' => ['user'],
                'customer' => new Customer(['id' => 21]),
            ]);
        }

        $dto = new class {
            #[Save(User::class)]
            public array $users;
        };
        $dto->users = $users;

        $this->bus->execute($dto);

        foreach ($users as $user) {
            $this->assertEquals($user, User::refresh($user));
        }
    }
}
