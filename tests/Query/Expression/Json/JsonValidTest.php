<?php

namespace Query\Expression\Json;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Json\Json;
use Bdf\Prime\Query\Expression\Json\JsonContains;
use Bdf\Prime\Query\Expression\Json\JsonValid;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class JsonValidTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->primeStart();

        $this->prime()->connections()->declareConnection('mysql', MYSQL_CONNECTION_DSN);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->unsetPrime();

        parent::tearDown();
    }

    public function test_build_for_mysql()
    {
        $connection = $this->prime()->connection('mysql');
        $query = $connection->builder();

        $this->assertSame('JSON_VALID(foo)', (new JsonValid('foo'))->build($query, $query->compiler()));
        $this->assertSame('JSON_VALID(JSON_EXTRACT(foo, \'$.bar\'))', (new JsonValid(Json::attr('foo')->bar->unquote(false)))->build($query, $query->compiler()));
    }

    public function test_build_for_sqlite()
    {
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();

        $this->assertSame('JSON_VALID(foo)', (new JsonValid('foo'))->build($query, $query->compiler()));
        $this->assertSame("JSON_VALID(foo->'$.bar')", (new JsonValid(Json::attr('foo')->bar->unquote(false)))->build($query, $query->compiler()));
    }
}
