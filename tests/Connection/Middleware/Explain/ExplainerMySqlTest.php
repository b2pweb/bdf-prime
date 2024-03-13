<?php

namespace Connection\Middleware\Explain;

use Bdf\Prime\CompositePkEntity;
use Bdf\Prime\Connection\Middleware\Explain\Explainer;
use Bdf\Prime\Connection\Middleware\Explain\Platform\MysqlExplainPlatform;
use Bdf\Prime\Connection\Middleware\Explain\QueryType;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEntity;
use PDO;
use PHPUnit\Framework\TestCase;

class ExplainerMySqlTest extends TestCase
{
    use PrimeTestCase;

    private Explainer $explainer;

    protected function setUp(): void
    {
        $this->configurePrime();

        $this->prime()->connections()->removeConnection('test');
        $this->prime()->connections()->declareConnection('test', MYSQL_CONNECTION_DSN);

        $connection = $this->prime()->connection('test');
        $connection->connect();

        $r = new \ReflectionProperty($connection, '_conn');
        $r->setAccessible(true);

        $this->explainer = new Explainer(
            $r->getValue($connection),
            new MysqlExplainPlatform()
        );

        $this->pack()
            ->declareEntity([
                TestEntity::class,
                TestEmbeddedEntity::class,
                CompositePkEntity::class,
            ])
        ;

        $this->initTestPack($this->pack());
        $this->pack()->initialize();
    }

    protected function tearDown(): void
    {
        $this->pack()->destroy();
        $this->unsetPrime();
    }

    public function initTestPack(TestPack $pack)
    {
        $pack->persist([
            $embedded1 = new TestEmbeddedEntity([
                'id' => 1,
                'name' => 'toto',
                'city' => 'foo',
            ]),
            $embedded10 = new TestEmbeddedEntity([
                'id' => 10,
                'name' => 'aqsz',
                'city' => 'poiu',
            ]),
            $embedded22 = new TestEmbeddedEntity([
                'id' => 2,
                'name' => 'tata',
                'city' => 'bar',
            ]),

            new TestEntity([
                'id' => 42,
                'name' => 'foo',
                'foreign' => $embedded1,
                'dateInsert' => new \DateTime('2018-01-01 00:00:00'),
            ]),
            new TestEntity([
                'id' => 12,
                'name' => 'bar',
                'foreign' => $embedded22,
                'dateInsert' => new \DateTime('2022-02-02 00:00:00'),
            ]),
            new TestEntity([
                'id' => 66,
                'name' => 'baz',
                'foreign' => $embedded10,
            ]),
            new TestEntity([
                'id' => 96,
                'name' => 'aze',
                'foreign' => $embedded10,
            ]),
            new CompositePkEntity([
                'key1' => 'toto',
                'key2' => 'tata',
                'value' => 'foo',
            ]),
        ]);

        // Spam the table to ensure the explain plan is not using a full scan
        for ($i = 100; $i < 1000; ++$i) {
            $pack->persist(new TestEmbeddedEntity([
                'id' => $i,
                'name' => bin2hex(random_bytes(5)),
                'city' => bin2hex(random_bytes(5)),
            ]));

            $pack->persist(new CompositePkEntity([
                'key1' => bin2hex(random_bytes(5)),
                'key2' => bin2hex(random_bytes(5)),
                'value' => bin2hex(random_bytes(5)),
            ]));
        }
    }

    public function test_explain_simple_query()
    {
        $result = $this->explainer->explain('SELECT 1 + 1');

        $this->assertEquals(QueryType::CONST, $result->type);
        $this->assertEmpty($result->tables);
        $this->assertEmpty($result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::CONST, $result->steps[0]->type);
        $this->assertNull($result->steps[0]->table);
        $this->assertNull($result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_pk_query()
    {
        $result = $this->explainer->explain(TestEntity::where('id', 42)->toRawSql());

        $this->assertEquals(QueryType::CONST, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEquals(['PRIMARY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertSame(1, $result->rows);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::CONST, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
        $this->assertSame(1, $result->steps[0]->rows);

        $result = $this->explainer->explain(TestEntity::where('id', 404)->toRawSql());

        $this->assertEquals(QueryType::CONST, $result->type);
        $this->assertEquals([], $result->tables);
        $this->assertEquals([], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::CONST, $result->steps[0]->type);
        $this->assertNull($result->steps[0]->table);
        $this->assertNull($result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);

        $result = $this->explainer->explain(TestEntity::where('id', [12, 45, 96])->toRawSql());

        $this->assertEquals(QueryType::INDEX, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEquals(['PRIMARY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertSame(3, $result->rows);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::INDEX, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
        $this->assertSame(3, $result->steps[0]->rows);
    }

    public function test_explain_scan_query()
    {
        $result = $this->explainer->explain(TestEntity::where('name', (new Like('foo'))->contains())->toRawSql());

        $this->assertEquals(QueryType::SCAN, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEmpty($result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertSame(4, $result->rows);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::SCAN, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertNull($result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
        $this->assertSame(4, $result->steps[0]->rows);
    }

    public function test_explain_index_query()
    {
        $result = $this->explainer->explain(TestEmbeddedEntity::where('name', 'toto')->toRawSql());

        $this->assertEquals(QueryType::PRIMARY, $result->type);
        $this->assertEquals(['foreign_'], $result->tables);
        $this->assertEquals(['IDX_127C71CEC0EB25A3'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertSame(1, $result->rows);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::PRIMARY, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertEquals('IDX_127C71CEC0EB25A3', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
        $this->assertSame(1, $result->steps[0]->rows);
    }

    public function test_explain_multi_index_or_query()
    {
        $result = $this->explainer->explain(TestEmbeddedEntity::where('id', 42)->orWhere('name', 'toto')->toRawSql());

        $this->assertEquals(QueryType::INDEX, $result->type);
        $this->assertEquals(['foreign_'], $result->tables);
        $this->assertEquals(['PRIMARY', 'IDX_127C71CEC0EB25A3'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertSame(2, $result->rows);
        $this->assertCount(1, $result->steps);

        $this->assertEquals(QueryType::INDEX, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY,IDX_127C71CEC0EB25A3', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
        $this->assertSame(2, $result->steps[0]->rows);
    }

    public function test_explain_covering_index_query()
    {
        $result = $this->explainer->explain(TestEmbeddedEntity::where('name', 'toto')->select(['id', 'name'])->toRawSql());

        $this->assertEquals(QueryType::PRIMARY, $result->type);
        $this->assertEquals(['foreign_'], $result->tables);
        $this->assertEquals(['IDX_127C71CEC0EB25A3'], $result->indexes);
        $this->assertTrue($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertSame(1, $result->rows);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::PRIMARY, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertEquals('IDX_127C71CEC0EB25A3', $result->steps[0]->index);
        $this->assertTrue($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
        $this->assertSame(1, $result->steps[0]->rows);
    }

    public function test_explain_covering_scan_query()
    {
        $result = $this->explainer->explain(TestEmbeddedEntity::select(['id', 'name'])->toRawSql());

        $this->assertEquals(QueryType::SCAN, $result->type);
        $this->assertEquals(['foreign_'], $result->tables);
        $this->assertEquals(['IDX_127C71CEC0EB25A3'], $result->indexes);
        $this->assertTrue($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertSame(903, $result->rows);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::SCAN, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertEquals('IDX_127C71CEC0EB25A3', $result->steps[0]->index);
        $this->assertTrue($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
        $this->assertSame(903, $result->steps[0]->rows);
    }

    public function test_explain_fk_query()
    {
        $result = $this->explainer->explain(TestEntity::where('foreign.city', 'toto')->toRawSql());

        $this->assertEquals(QueryType::SCAN, $result->type);
        $this->assertEquals(['test_', 'foreign_'], $result->tables);
        $this->assertEquals(['PRIMARY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertSame(5, $result->rows);
        $this->assertCount(2, $result->steps);

        $this->assertEquals(QueryType::SCAN, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertNull($result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
        $this->assertSame(4, $result->steps[0]->rows);

        $this->assertEquals(QueryType::PRIMARY, $result->steps[1]->type);
        $this->assertEquals('foreign_', $result->steps[1]->table);
        $this->assertEquals('PRIMARY', $result->steps[1]->index);
        $this->assertFalse($result->steps[1]->covering);
        $this->assertFalse($result->steps[1]->temporary);
        $this->assertSame(1, $result->steps[1]->rows);
    }

    public function test_explain_pk_and_filter_query()
    {
        $result = $this->explainer->explain(TestEntity::where('id', 42)->where('name', 'foo')->toRawSql());

        $this->assertEquals(QueryType::CONST, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEquals(['PRIMARY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertSame(1, $result->rows);
        $this->assertEquals(QueryType::CONST, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_pk_composite()
    {
        $result = $this->explainer->explain(CompositePkEntity::where('key1', 'toto')->where('key2', 'tata')->toRawSql());

        $this->assertEquals(QueryType::CONST, $result->type);
        $this->assertEquals(['_composite_pk'], $result->tables);
        $this->assertEquals(['PRIMARY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertSame(1, $result->rows);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::CONST, $result->steps[0]->type);
        $this->assertEquals('_composite_pk', $result->steps[0]->table);
        $this->assertEquals('PRIMARY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_order_using_temporary()
    {
        $result = $this->explainer->explain(TestEmbeddedEntity::where('id', [12, 45, 96])->order('name')->toRawSql());

        $this->assertEquals(QueryType::INDEX, $result->type);
        $this->assertEquals(['foreign_'], $result->tables);
        $this->assertEquals(['PRIMARY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertTrue($result->temporary);
        $this->assertSame(3, $result->rows);
        $this->assertCount(1, $result->steps);

        $this->assertEquals(QueryType::INDEX, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertTrue($result->steps[0]->temporary);
        $this->assertSame(3, $result->steps[0]->rows);
    }

    public function test_explain_order_using_index()
    {
        $result = $this->explainer->explain(TestEmbeddedEntity::select(['id', 'name'])->order('name')->toRawSql());

        $this->assertEquals(QueryType::SCAN, $result->type);
        $this->assertEquals(['foreign_'], $result->tables);
        $this->assertEquals(['IDX_127C71CEC0EB25A3'], $result->indexes);
        $this->assertTrue($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertSame(903, $result->rows);

        $this->assertEquals(QueryType::SCAN, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertEquals('IDX_127C71CEC0EB25A3', $result->steps[0]->index);
        $this->assertTrue($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_with_automatic_index()
    {
        $result = $this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.city = t0.city WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city');

        $this->assertEquals(QueryType::SCAN, $result->type);
        $this->assertEquals(['foreign_', 'foreign_'], $result->tables);
        $this->assertEquals([], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertTrue($result->temporary);
        $this->assertCount(2, $result->steps);
        $this->assertSame(1806, $result->rows);

        $this->assertEquals(QueryType::SCAN, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertNull($result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertTrue($result->steps[0]->temporary);
        $this->assertSame(903, $result->steps[0]->rows);

        $this->assertEquals(QueryType::SCAN, $result->steps[1]->type);
        $this->assertEquals('foreign_', $result->steps[1]->table);
        $this->assertNull($result->steps[1]->index);
        $this->assertFalse($result->steps[1]->covering);
        $this->assertFalse($result->steps[1]->temporary);
        $this->assertSame(903, $result->steps[1]->rows);
    }

    public function test_explain_subquery()
    {
        $result = $this->explainer->explain(
            TestEntity::where(
                'foreign.id',
                ':in',
                TestEmbeddedEntity::repository()->queries()->fromAlias('emb')->where('name', 'toto')->select('id')
            )
                ->toRawSql()
        );

        $this->assertEquals(QueryType::SCAN, $result->type);
        $this->assertEquals(['foreign_', 'test_'], $result->tables);
        $this->assertEquals(['IDX_127C71CEC0EB25A3'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(2, $result->steps);
        $this->assertSame(5, $result->rows);

        $this->assertEquals(QueryType::PRIMARY, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertEquals('IDX_127C71CEC0EB25A3', $result->steps[0]->index);
        $this->assertTrue($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
        $this->assertSame(1, $result->steps[0]->rows);

        $this->assertEquals(QueryType::SCAN, $result->steps[1]->type);
        $this->assertEquals('test_', $result->steps[1]->table);
        $this->assertNull($result->steps[1]->index);
        $this->assertFalse($result->steps[1]->covering);
        $this->assertFalse($result->steps[1]->temporary);
        $this->assertSame(4, $result->steps[1]->rows);
    }

    public function test_explain_update()
    {
        $result = $this->explainer->explain('UPDATE test_ SET name = "toto" WHERE id = 42');

        $this->assertEquals(QueryType::INDEX, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEquals(['PRIMARY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertSame(1, $result->rows);
        $this->assertEquals(QueryType::INDEX, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_delete()
    {
        $result = $this->explainer->explain('DELETE FROM test_ WHERE id = 42');

        $this->assertEquals(QueryType::INDEX, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEquals(['PRIMARY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertSame(1, $result->rows);
        $this->assertEquals(QueryType::INDEX, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_ignore_queries()
    {
        $this->assertNull($this->explainer->explain('EXPLAIN SELECT * FROM test_ SET name = "toto" WHERE id = 42'));
        $this->assertNull($this->explainer->explain('INSERT INTO test_(id, name) VALUES (42, "toto")'));
    }

    public function test_explain_use_parameters()
    {
        $query = CompositePkEntity::where('key1', 'toto')->where('key2', 'tata');
        $query->compile();

        $result = $this->explainer->explain($query->toSql(), [1 => 'toto', 2 => 'tata']);

        $this->assertEquals(QueryType::CONST, $result->type);
        $this->assertEquals(['_composite_pk'], $result->tables);
        $this->assertEquals(['PRIMARY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertSame(1, $result->rows);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::CONST, $result->steps[0]->type);
        $this->assertEquals('_composite_pk', $result->steps[0]->table);
        $this->assertEquals('PRIMARY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_use_typed_parameters()
    {
        $result = $this->explainer->explain(
            'SELECT t0.* FROM test_ t0 WHERE t0.id > ? ORDER BY t0.id LIMIT ? OFFSET ?',
            [1 => 42, 2 => 5, 3 => 15],
            [1 => PDO::PARAM_INT, 2 => PDO::PARAM_INT, 3 => PDO::PARAM_INT]
        );

        $this->assertEquals(QueryType::INDEX, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEquals(['PRIMARY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::INDEX, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }
}
