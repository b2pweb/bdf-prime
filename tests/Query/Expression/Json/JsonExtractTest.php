<?php

namespace Query\Expression\Json;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Json\Json;
use Bdf\Prime\Query\Expression\Json\JsonContains;
use Bdf\Prime\Query\Expression\Json\JsonExtract;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class JsonExtractTest extends TestCase
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

        $this->assertSame("JSON_UNQUOTE(JSON_EXTRACT(foo, '$.bar'))", (new JsonExtract('foo', '$.bar'))->build($query, $query->compiler()));
        $this->assertSame("JSON_EXTRACT(foo, '$.bar')", (new JsonExtract('foo', '$.bar', false))->build($query, $query->compiler()));
    }

    public function test_build_for_sqlite()
    {
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();

        $this->assertSame("foo->>'$.bar'", (new JsonExtract('foo', '$.bar'))->build($query, $query->compiler()));
        $this->assertSame("foo->'$.bar'", (new JsonExtract('foo', '$.bar', false))->build($query, $query->compiler()));
    }
}
