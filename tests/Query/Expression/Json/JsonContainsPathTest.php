<?php

namespace Query\Expression\Json;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Json\Json;
use Bdf\Prime\Query\Expression\Json\JsonContainsPath;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class JsonContainsPathTest extends TestCase
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

        $this->assertSame('JSON_CONTAINS_PATH(foo, "all", \'$.bar\')', (new JsonContainsPath('foo', '$.bar'))->build($query, $query->compiler()));
        $this->assertSame('JSON_CONTAINS_PATH(JSON_EXTRACT(foo, \'$.bar\'), "all", \'$[2]\')', (new JsonContainsPath(Json::attr('foo')->bar->unquote(false), '$[2]'))->build($query, $query->compiler()));
    }

    public function test_build_for_sqlite()
    {
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();


        $this->assertSame("'$.bar' IN (SELECT fullkey FROM json_tree(foo))", (new JsonContainsPath('foo', '$.bar'))->build($query, $query->compiler()));
        $this->assertSame("'$[2]' IN (SELECT fullkey FROM json_tree(foo->'$.bar'))", (new JsonContainsPath(Json::attr('foo')->bar->unquote(false), '$[2]'))->build($query, $query->compiler()));
    }
}
