<?php

namespace Bus\Query\Configurator;

use Bdf\Prime\Bus\Query\Configurator\Limit;
use Bdf\Prime\Bus\Query\Configurator\Page;
use Bdf\Prime\Bus\Query\Execution\QueryExecutionMethod;
use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Bus\Query\PrimeQueryBus;
use Bdf\Prime\Customer;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Pagination\Paginator;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

use function range;

class PageTest extends TestCase
{
    use PrimeTestCase;

    private PrimeQueryBus $bus;

    protected function setUp(): void
    {
        $this->configurePrime();

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
        $this->prime()->repository(User::class)->schema()->migrate();

        for ($i = 1; $i <= 20; $i++) {
            (new User([
                'id' => $i,
                'name' => 'user'.$i,
                'roles' => ['test'],
                'customer' => new Customer(['id' => 1])
            ]))->insert();
        }

        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::Paginate), Limit(5)] class {
            #[Page]
            public int $page;
        };

        /** @var Paginator<User> $results */
        $results = $this->bus->query($dto);
        $this->assertSame(1, $results->page());
        $this->assertCount(5, $results);
        $this->assertSame(20, $results->size());
        $this->assertEquals(range(1, 5), $results->map(fn(User $user) => $user->id)->all());

        $dto->page = 3;
        $results = $this->bus->query($dto);
        $this->assertSame(3, $results->page());
        $this->assertCount(5, $results);
        $this->assertSame(20, $results->size());
        $this->assertEquals(range(11, 15), $results->map(fn(User $user) => $user->id)->all());
    }

    public function test_with_default()
    {
        $this->prime()->repository(User::class)->schema()->migrate();

        for ($i = 1; $i <= 20; $i++) {
            (new User([
                'id' => $i,
                'name' => 'user'.$i,
                'roles' => ['test'],
                'customer' => new Customer(['id' => 1])
            ]))->insert();
        }

        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::Paginate), Limit(5)] class {
            #[Page(2)]
            public int $page;
        };

        /** @var Paginator<User> $results */
        $results = $this->bus->query($dto);
        $this->assertSame(2, $results->page());
        $this->assertCount(5, $results);
        $this->assertSame(20, $results->size());
        $this->assertEquals(range(6, 10), $results->map(fn(User $user) => $user->id)->all());

        $dto->page = 3;
        $results = $this->bus->query($dto);
        $this->assertSame(3, $results->page());
        $this->assertCount(5, $results);
        $this->assertSame(20, $results->size());
        $this->assertEquals(range(11, 15), $results->map(fn(User $user) => $user->id)->all());
    }
}
