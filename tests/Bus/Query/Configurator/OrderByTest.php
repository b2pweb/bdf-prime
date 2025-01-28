<?php

namespace Bus\Query\Configurator;

use Bdf\Prime\Bus\Query\Configurator\OrderBy;
use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Bus\Query\PrimeQueryBus;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

class OrderByTest extends TestCase
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

    public function test_single_order()
    {
        $dto = new #[PrimeQuery(User::class), OrderBy('name')] class {};

        $this->assertSame('SELECT t0.* FROM user_ t0 ORDER BY t0.name_ ASC', $this->bus->generateQuery($dto)->toSql());
    }

    public function test_order_desc()
    {
        $dto = new #[PrimeQuery(User::class), OrderBy('name', 'DESC')] class {};

        $this->assertSame('SELECT t0.* FROM user_ t0 ORDER BY t0.name_ DESC', $this->bus->generateQuery($dto)->toSql());
    }

    public function test_multiple_orders()
    {
        $dto = new #[PrimeQuery(User::class), OrderBy('name', 'DESC'), OrderBy('customer.id')] class {};

        $this->assertSame('SELECT t0.* FROM user_ t0 ORDER BY t0.name_ DESC, t0.customer_id ASC', $this->bus->generateQuery($dto)->toSql());
    }
}
