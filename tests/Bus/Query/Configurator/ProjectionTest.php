<?php

namespace Bus\Query\Configurator;

use Bdf\Prime\Bus\Query\Configurator\Projection;
use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Bus\Query\PrimeQueryBus;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Attribute;
use Bdf\Prime\Query\Expression\Raw;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

class ProjectionTest extends TestCase
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

    public function test_simple()
    {
        $dto = new #[PrimeQuery(User::class), Projection('name')] class {};

        $this->assertSame('SELECT t0.name_ FROM user_ t0', $this->bus->generateQuery($dto)->toSql());
    }

    public function test_multiple()
    {
        $dto = new #[PrimeQuery(User::class), Projection(['name', 'customer.id'])] class {};

        $this->assertSame('SELECT t0.name_, t0.customer_id FROM user_ t0', $this->bus->generateQuery($dto)->toSql());
    }

    public function test_with_alias()
    {
        $dto = new #[PrimeQuery(User::class), Projection(['a' => 'name', 'b' => 'customer.id'])] class {};

        $this->assertSame('SELECT t0.name_ as a, t0.customer_id as b FROM user_ t0', $this->bus->generateQuery($dto)->toSql());
    }

    public function test_single_expression()
    {
        $dto = new #[PrimeQuery(User::class), Projection(new Attribute('name', 'MD5(%s)'))] class {};

        $this->assertSame('SELECT MD5(t0.name_) FROM user_ t0', $this->bus->generateQuery($dto)->toSql());
    }

    public function test_multiple_expressions()
    {
        $dto = new #[PrimeQuery(User::class), Projection(['h' => new Attribute('name', 'MD5(%s)'), 'i' => new Raw('RANDOM()')])] class {};

        $this->assertSame('SELECT MD5(t0.name_) as h, RANDOM() as i FROM user_ t0', $this->bus->generateQuery($dto)->toSql());
    }
}
