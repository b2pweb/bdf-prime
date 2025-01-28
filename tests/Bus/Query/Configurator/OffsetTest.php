<?php

namespace Bus\Query\Configurator;

use Bdf\Prime\Bus\Query\Configurator\Offset;
use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Bus\Query\PrimeQueryBus;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

class OffsetTest extends TestCase
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

    public function test_empty()
    {
        $dto = new #[PrimeQuery(User::class), Offset] class {};

        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0', $query->toSql());
    }

    public function test_constant_on_class()
    {
        $dto = new #[PrimeQuery(User::class), Offset(5)] class {};

        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0 LIMIT -1 OFFSET 5', $query->toSql());
    }

    public function test_constant_on_property_with_default()
    {
        $dto = new #[PrimeQuery(User::class)] class {
            #[Offset(5)]
            public int $offset;
        };

        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0 LIMIT -1 OFFSET 5', $query->toSql());

        $dto->offset = 10;
        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0 LIMIT -1 OFFSET 10', $query->toSql());
    }

    public function test_constant_on_property_without_default()
    {
        $dto = new #[PrimeQuery(User::class)] class {
            #[Offset]
            public int $offset;
        };

        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0', $query->toSql());

        $dto->offset = 10;
        $query = $this->bus->generateQuery($dto);
        $this->assertSame('SELECT t0.* FROM user_ t0 LIMIT -1 OFFSET 10', $query->toSql());
    }
}
