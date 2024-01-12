<?php

namespace Query\Expression\Json;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Json\JsonSet;
use Bdf\Prime\Query\Expression\Raw;
use PHPUnit\Framework\TestCase;

class JsonSetTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->primeStart();

        $this->prime()->connections()->declareConnection('mariadb', MYSQL_CONNECTION_DSN.'?serverVersion=mariadb-11.0.1');
        $this->prime()->connections()->declareConnection('mysql', MYSQL_CONNECTION_DSN.'?serverVersion=11.0.1');
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->unsetPrime();

        parent::tearDown();
    }

    public function test_build_for_mariadb()
    {
        $connection = $this->prime()->connection('mariadb');
        $query = $connection->builder();

        $this->assertSame("JSON_SET(foo, '$.bar', JSON_COMPACT('null'))", (new JsonSet('foo', '$.bar', null))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', JSON_COMPACT('true'))", (new JsonSet('foo', '$.bar', true))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', JSON_COMPACT('123'))", (new JsonSet('foo', '$.bar', 123))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', JSON_COMPACT('12.3'))", (new JsonSet('foo', '$.bar', 12.3))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', JSON_COMPACT('\\\"bar\\\"'))", (new JsonSet('foo', '$.bar', 'bar'))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', JSON_COMPACT('[\\\"bar\\\",123]'))", (new JsonSet('foo', '$.bar', ['bar', 123]))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', JSON_COMPACT('{\\\"bar\\\":\\\"baz\\\"}'))", (new JsonSet('foo', '$.bar', ['bar' => 'baz']))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET('{}', '$.bar', JSON_COMPACT('{\\\"bar\\\":\\\"baz\\\"}'))", (new JsonSet(new Raw("'{}'"), '$.bar', ['bar' => 'baz']))->build($query, $query->compiler()));
    }

    public function test_build_for_mysql()
    {
        $connection = $this->prime()->connection('mysql');
        $query = $connection->builder();

        $this->assertSame("JSON_SET(foo, '$.bar', CAST('null' AS JSON))", (new JsonSet('foo', '$.bar', null))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', CAST('true' AS JSON))", (new JsonSet('foo', '$.bar', true))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', CAST('123' AS JSON))", (new JsonSet('foo', '$.bar', 123))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', CAST('12.3' AS JSON))", (new JsonSet('foo', '$.bar', 12.3))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', CAST('\\\"bar\\\"' AS JSON))", (new JsonSet('foo', '$.bar', 'bar'))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', CAST('[\\\"bar\\\",123]' AS JSON))", (new JsonSet('foo', '$.bar', ['bar', 123]))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', CAST('{\\\"bar\\\":\\\"baz\\\"}' AS JSON))", (new JsonSet('foo', '$.bar', ['bar' => 'baz']))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET('{}', '$.bar', CAST('{\\\"bar\\\":\\\"baz\\\"}' AS JSON))", (new JsonSet(new Raw("'{}'"), '$.bar', ['bar' => 'baz']))->build($query, $query->compiler()));
    }

    public function test_build_for_sqlite()
    {
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();

        $this->assertSame("JSON_SET(foo, '$.bar', json('null'))", (new JsonSet('foo', '$.bar', null))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', json('true'))", (new JsonSet('foo', '$.bar', true))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', json('123'))", (new JsonSet('foo', '$.bar', 123))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', json('12.3'))", (new JsonSet('foo', '$.bar', 12.3))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', json('\"bar\"'))", (new JsonSet('foo', '$.bar', 'bar'))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', json('[\"bar\",123]'))", (new JsonSet('foo', '$.bar', ['bar', 123]))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET(foo, '$.bar', json('{\"bar\":\"baz\"}'))", (new JsonSet('foo', '$.bar', ['bar' => 'baz']))->build($query, $query->compiler()));
        $this->assertSame("JSON_SET('{}', '$.bar', json('{\"bar\":\"baz\"}'))", (new JsonSet(new Raw("'{}'"), '$.bar', ['bar' => 'baz']))->build($query, $query->compiler()));
    }
}
