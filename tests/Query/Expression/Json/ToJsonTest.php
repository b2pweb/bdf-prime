<?php

namespace Query\Expression\Json;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Attribute;
use Bdf\Prime\Query\Expression\Json\JsonSet;
use Bdf\Prime\Query\Expression\Json\ToJson;
use Bdf\Prime\Query\Expression\Raw;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\TestCase;

class ToJsonTest extends TestCase
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
        $this->prime()->connections()->declareConnection('other', [
            'platform' => new PostgreSQLPlatform(),
        ] + MYSQL_CONNECTION_PARAMETERS);
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

        $this->assertSame("JSON_COMPACT('null')", (new ToJson(null))->build($query, $query->compiler()));
        $this->assertSame("JSON_COMPACT('true')", (new ToJson(true))->build($query, $query->compiler()));
        $this->assertSame("JSON_COMPACT('123')", (new ToJson(123))->build($query, $query->compiler()));
        $this->assertSame("JSON_COMPACT('12.3')", (new ToJson(12.3))->build($query, $query->compiler()));
        $this->assertSame("JSON_COMPACT('\\\"bar\\\"')", (new ToJson('bar'))->build($query, $query->compiler()));
        $this->assertSame("JSON_COMPACT('[\\\"bar\\\",123]')", (new ToJson(['bar', 123]))->build($query, $query->compiler()));
        $this->assertSame("JSON_COMPACT('{\\\"bar\\\":\\\"baz\\\"}')", (new ToJson(['bar' => 'baz']))->build($query, $query->compiler()));
        $this->assertSame("JSON_COMPACT(foo)", (new ToJson(new Attribute('foo')))->build($query, $query->compiler()));
    }

    public function test_build_for_mysql()
    {
        $connection = $this->prime()->connection('mysql');
        $query = $connection->builder();

        $this->assertSame("CAST('null' AS JSON)", (new ToJson(null))->build($query, $query->compiler()));
        $this->assertSame("CAST('true' AS JSON)", (new ToJson(true))->build($query, $query->compiler()));
        $this->assertSame("CAST('123' AS JSON)", (new ToJson(123))->build($query, $query->compiler()));
        $this->assertSame("CAST('12.3' AS JSON)", (new ToJson(12.3))->build($query, $query->compiler()));
        $this->assertSame("CAST('\\\"bar\\\"' AS JSON)", (new ToJson('bar'))->build($query, $query->compiler()));
        $this->assertSame("CAST('[\\\"bar\\\",123]' AS JSON)", (new ToJson(['bar', 123]))->build($query, $query->compiler()));
        $this->assertSame("CAST('{\\\"bar\\\":\\\"baz\\\"}' AS JSON)", (new ToJson(['bar' => 'baz']))->build($query, $query->compiler()));
        $this->assertSame("CAST(foo AS JSON)", (new ToJson(new Attribute('foo')))->build($query, $query->compiler()));
    }

    public function test_build_for_sqlite()
    {
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();

        $this->assertSame("json('null')", (new ToJson(null))->build($query, $query->compiler()));
        $this->assertSame("json('true')", (new ToJson(true))->build($query, $query->compiler()));
        $this->assertSame("json('123')", (new ToJson(123))->build($query, $query->compiler()));
        $this->assertSame("json('12.3')", (new ToJson(12.3))->build($query, $query->compiler()));
        $this->assertSame("json('\"bar\"')", (new ToJson('bar'))->build($query, $query->compiler()));
        $this->assertSame("json('[\"bar\",123]')", (new ToJson(['bar', 123]))->build($query, $query->compiler()));
        $this->assertSame("json('{\"bar\":\"baz\"}')", (new ToJson(['bar' => 'baz']))->build($query, $query->compiler()));
        $this->assertSame("json(foo)", (new ToJson(new Attribute('foo')))->build($query, $query->compiler()));
    }

    public function test_build_for_other_platform()
    {
        $connection = $this->prime()->connection('other');
        $query = $connection->builder();

        $this->assertSame("null", (new ToJson(null))->build($query, $query->compiler()));
        $this->assertSame("true", (new ToJson(true))->build($query, $query->compiler()));
        $this->assertSame("false", (new ToJson(false))->build($query, $query->compiler()));
        $this->assertSame("123", (new ToJson(123))->build($query, $query->compiler()));
        $this->assertSame("12.3", (new ToJson(12.3))->build($query, $query->compiler()));
        $this->assertSame("'bar'", (new ToJson('bar'))->build($query, $query->compiler()));
        $this->assertSame("JSON_ARRAY('bar', 123)", (new ToJson(['bar', 123]))->build($query, $query->compiler()));
        $this->assertSame("JSON_OBJECT('bar', 'baz')", (new ToJson(['bar' => 'baz']))->build($query, $query->compiler()));
        $this->assertSame("foo", (new ToJson(new Attribute('foo')))->build($query, $query->compiler()));
    }
}
