<?php

namespace Bus\Query\Configurator;

use Bdf\Prime\Bus\Query\Configurator\Limit;
use Bdf\Prime\Bus\Query\Execution\QueryExecutionMethod;
use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Bus\Query\PrimeQueryBus;
use Bdf\Prime\Customer;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Pagination\Paginator;
use Bdf\Prime\Query\Pagination\Walker;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

use function array_map;
use function iterator_to_array;
use function range;
use function var_export;

class LimitTest extends TestCase
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
        $this->unsetPrime();
        unset($this->bus);
    }

    public function test_no_values()
    {
        $dto = new #[PrimeQuery(User::class), Limit] class {};

        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0', $query->toSql());
        $this->assertSame([], $query->getBindings());
    }

    public function test_constant_limit()
    {
        $dto = new #[PrimeQuery(User::class), Limit(10)] class {};

        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0 LIMIT 10', $query->toSql());
        $this->assertSame([], $query->getBindings());
    }

    public function test_constant_offset()
    {
        $dto = new #[PrimeQuery(User::class), Limit(offset: 10)] class {};

        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0 LIMIT -1 OFFSET 10', $query->toSql());
        $this->assertSame([], $query->getBindings());
    }

    public function test_constant_limit_and_offset()
    {
        $dto = new #[PrimeQuery(User::class), Limit(10, 30)] class {};

        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0 LIMIT 10 OFFSET 30', $query->toSql());
        $this->assertSame([], $query->getBindings());
    }

    public function test_on_empty_property()
    {
        $dto = new #[PrimeQuery(User::class)] class {
            #[Limit]
            public int $limit;
        };

        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0', $query->toSql());
        $this->assertSame([], $query->getBindings());

        $dto->limit = 5;
        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0 LIMIT 5', $query->toSql());
        $this->assertSame([], $query->getBindings());
    }

    public function test_on_empty_property_with_default()
    {
        $dto = new #[PrimeQuery(User::class)] class {
            #[Limit(50)]
            public int $limit;
        };

        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0 LIMIT 50', $query->toSql());
        $this->assertSame([], $query->getBindings());

        $dto->limit = 10;
        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0 LIMIT 10', $query->toSql());
        $this->assertSame([], $query->getBindings());
    }

    public function test_limit_and_offset_on_property()
    {
        $dto = new #[PrimeQuery(User::class)] class {
            #[Limit(offset: 10)]
            public int $limit = 5;
        };

        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0 LIMIT 5 OFFSET 10', $query->toSql());
        $this->assertSame([], $query->getBindings());
    }

    public function test_limit_on_pagination()
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

        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::Paginate)] class {
            #[Limit]
            public int $limit = 5;
        };

        /** @var Paginator<User> $results */
        $results = $this->bus->query($dto);

        $this->assertSame(1, $results->page());
        $this->assertCount(5, $results);
        $this->assertSame(20, $results->size());
        $this->assertEquals(range(1, 5), $results->map(fn(User $user) => $user->id)->all());
    }

    public function test_limit_on_walker()
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

        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::Walk)] class {
            #[Limit]
            public int $limit = 5;
        };

        /** @var Walker<User> $results */
        $results = $this->bus->query($dto);

        $this->assertEquals(range(1, 20), array_map(fn(User $user) => $user->id, iterator_to_array($results)));
        $this->assertSame(5, $results->limit());
    }
}
