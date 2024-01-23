<?php

namespace Query\Expression\Json;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Json\Json;
use Bdf\Prime\Query\Expression\Json\JsonContains;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class JsonContainsTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->primeStart();

        $this->prime()->connections()->declareConnection('mysql', MYSQL_CONNECTION_PARAMETERS);
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

        $this->assertSame('JSON_CONTAINS(foo, \'\"bar\"\')', (new JsonContains('foo', 'bar'))->build($query, $query->compiler()));
        $this->assertSame('JSON_CONTAINS(foo, \'123\')', (new JsonContains('foo', 123))->build($query, $query->compiler()));
        $this->assertSame('JSON_CONTAINS(JSON_EXTRACT(foo, \'$.bar\'), \'123\')', (new JsonContains(Json::attr('foo')->bar->unquote(false), 123))->build($query, $query->compiler()));
    }

    public function test_build_for_sqlite()
    {
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();

        $this->assertSame("'bar' IN (SELECT atom FROM json_each(foo))", (new JsonContains('foo', 'bar'))->build($query, $query->compiler()));
        $this->assertSame("123 IN (SELECT atom FROM json_each(foo))", (new JsonContains('foo', 123))->build($query, $query->compiler()));
        $this->assertSame("123 IN (SELECT atom FROM json_each(foo->'$.bar'))", (new JsonContains(Json::attr('foo')->bar->unquote(false), 123))->build($query, $query->compiler()));
    }

    public function test_not_scalar_value()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The candidate value must be a scalar array given');

        new JsonContains('foo', []);
    }
}
