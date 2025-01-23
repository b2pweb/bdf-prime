<?php

namespace Bus\Query\Configurator;

use Bdf\Prime\Bus\Query\Configurator\From;
use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Bus\Query\PrimeQueryBus;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

class FromTest extends TestCase
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

    public function test_success()
    {
        $dto = new #[PrimeQuery(connection: 'test'), From('foo')] class {};
        $this->assertSame('SELECT * FROM foo', $this->bus->generateQuery($dto)->toSql());
    }

    public function test_success_with_alias()
    {
        $dto = new #[PrimeQuery(connection: 'test'), From('foo', 'bar')] class {};
        $this->assertSame('SELECT * FROM foo bar', $this->bus->generateQuery($dto)->toSql());
    }

    public function test_multiple_from()
    {
        $dto = new #[PrimeQuery(connection: 'test'), From('foo'), From('bar')] class {};
        $this->assertSame('SELECT * FROM foo, bar', $this->bus->generateQuery($dto)->toSql());
    }

    public function test_entity_and_from()
    {
        $dto = new #[PrimeQuery(User::class), From('foo')] class {};
        $this->assertSame('SELECT t0.* FROM user_ t0, foo', $this->bus->generateQuery($dto)->toSql());
    }
}
