<?php

namespace Query\Expression\Json;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Json\Json;
use Bdf\Prime\Query\Expression\Json\JsonContains;
use Bdf\Prime\Query\Expression\Json\JsonContainsPath;
use Bdf\Prime\Query\Expression\Json\JsonInsert;
use Bdf\Prime\Query\Expression\Json\JsonReplace;
use Bdf\Prime\Query\Expression\Json\JsonSet;
use Bdf\Prime\Query\Expression\Json\JsonValid;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->primeStart();
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->unsetPrime();

        parent::tearDown();
    }

    public function test_build_path_using_get()
    {
        // @todo env pour config mysql
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();

        $this->assertSame("foo->>'$.bar.baz'", Json::attr('foo')->bar->baz->build($query, $query->compiler()));

        $json = Json::attr('foo');

        // Does not modify the original object
        $this->assertNotSame($json, $json->bar);
        $this->assertSame("foo->>'$'", $json->build($query, $query->compiler()));
    }

    public function test_build_path_using_array_offset()
    {
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();

        $this->assertSame("foo->>'$.bar[1][2]'", Json::attr('foo')->bar[1][2]->build($query, $query->compiler()));

        $json = Json::attr('foo');

        // Does not modify the original object
        $this->assertNotSame($json, $json[1]);
        $this->assertSame("foo->>'$'", $json->build($query, $query->compiler()));
    }

    public function test_unquote()
    {
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();

        $json = Json::attr('foo')->bar;

        $this->assertSame("foo->'$.bar'", $json->unquote(false)->build($query, $query->compiler()));

        // Does not modify the original object
        $this->assertNotSame($json, $json->unquote(false));
        $this->assertSame("foo->>'$.bar'", $json->build($query, $query->compiler()));
    }

    public function test_valid()
    {
        $this->assertEquals(new JsonValid('foo'), Json::valid('foo'));
    }

    public function test_contains()
    {
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();

        $this->assertEquals(new JsonContains(Json::attr('foo')->bar->unquote(false), 123), Json::attr('foo')->bar->contains(123));
        $this->assertSame("123 IN (SELECT atom FROM json_each(foo->'$.bar'))", Json::attr('foo')->bar->contains(123)->build($query, $query->compiler()));
    }

    public function test_hasPath()
    {
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();

        $this->assertEquals(new JsonContainsPath('foo', '$.bar'), Json::attr('foo')->bar->hasPath());
        $this->assertSame("'$.bar' IN (SELECT fullkey FROM json_tree(foo))", Json::attr('foo')->bar->hasPath()->build($query, $query->compiler()));

        $this->assertEquals(new JsonContainsPath('foo', '$.bar.baz'), Json::attr('foo')->bar->hasPath('baz'));
        $this->assertSame("'$.bar.baz' IN (SELECT fullkey FROM json_tree(foo))", Json::attr('foo')->bar->hasPath('baz')->build($query, $query->compiler()));

        $this->assertEquals(new JsonContainsPath('foo', '$.bar[1]'), Json::attr('foo')->bar->hasPath('[1]'));
        $this->assertSame("'$.bar[1]' IN (SELECT fullkey FROM json_tree(foo))", Json::attr('foo')->bar->hasPath('[1]')->build($query, $query->compiler()));

        $this->assertEquals(new JsonContainsPath('foo', '$.bar.baz'), Json::attr('foo')->bar->hasPath('.baz'));
        $this->assertSame("'$.bar.baz' IN (SELECT fullkey FROM json_tree(foo))", Json::attr('foo')->bar->hasPath('.baz')->build($query, $query->compiler()));
    }

    public function test_set()
    {
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();

        $this->assertEquals(new JsonSet('foo', '$.bar', 123), Json::attr('foo')->bar->set(123));
        $this->assertSame("JSON_SET(foo, '$.bar', json('123'))", Json::attr('foo')->bar->set(123)->build($query, $query->compiler()));
    }

    public function test_insert()
    {
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();

        $this->assertEquals(new JsonInsert('foo', '$.bar', 123), Json::attr('foo')->bar->insert(123));
        $this->assertSame("JSON_INSERT(foo, '$.bar', json('123'))", Json::attr('foo')->bar->insert(123)->build($query, $query->compiler()));
    }

    public function test_replace()
    {
        $connection = $this->prime()->connection('test');
        $query = $connection->builder();

        $this->assertEquals(new JsonReplace('foo', '$.bar', 123), Json::attr('foo')->bar->replace(123));
        $this->assertSame("JSON_REPLACE(foo, '$.bar', json('123'))", Json::attr('foo')->bar->replace(123)->build($query, $query->compiler()));
    }

    public function test_disallow_offset_set()
    {
        $this->expectException(\BadMethodCallException::class);

        Json::attr('foo')->bar[1] = 123;
    }

    public function test_disallow_offset_isset()
    {
        $this->expectException(\BadMethodCallException::class);

        isset(Json::attr('foo')->bar[1]);
    }

    public function test_disallow_offset_unset()
    {
        $this->expectException(\BadMethodCallException::class);

        unset(Json::attr('foo')->bar[1]);
    }
}
