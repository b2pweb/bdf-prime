<?php

namespace Connection\Middleware\Explain;

use Bdf\Prime\CompositePkEntity;
use Bdf\Prime\Connection\Middleware\Explain\Explainer;
use Bdf\Prime\Connection\Middleware\Explain\Platform\SqliteExplainPlatform;
use Bdf\Prime\Connection\Middleware\Explain\QueryType;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;

class ExplainerTest extends TestCase
{
    use PrimeTestCase;

    private Explainer $explainer;

    protected function setUp(): void
    {
        $this->configurePrime();

        $connection = $this->prime()->connection('test');
        $connection->connect();

        $r = new \ReflectionProperty($connection, '_conn');
        $r->setAccessible(true);

        $this->explainer = new Explainer(
            $r->getValue($connection),
            new SqliteExplainPlatform()
        );
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
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
        TestEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain(TestEntity::where('id', 42)->toRawSql());

        $this->assertEquals(QueryType::PRIMARY, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEquals(['PRIMARY KEY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::PRIMARY, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY KEY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);

        $result = $this->explainer->explain(TestEntity::where('id', [12, 45, 96])->toRawSql());

        $this->assertEquals(QueryType::PRIMARY, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEquals(['PRIMARY KEY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::PRIMARY, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY KEY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_scan_query()
    {
        TestEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain(TestEntity::where('name', (new Like('foo'))->contains())->toRawSql());

        $this->assertEquals(QueryType::SCAN, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEmpty($result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::SCAN, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertNull($result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_index_query()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain(TestEmbeddedEntity::where('name', 'toto')->toRawSql());

        $this->assertEquals(QueryType::INDEX, $result->type);
        $this->assertEquals(['foreign_'], $result->tables);
        $this->assertEquals(['IDX_127C71CEC0EB25A3'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::INDEX, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertEquals('IDX_127C71CEC0EB25A3', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_multi_index_or_query()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain(TestEmbeddedEntity::where('id', 42)->orWhere('name', 'toto')->toRawSql());

        $this->assertEquals(QueryType::INDEX, $result->type);
        $this->assertEquals(['foreign_', 'foreign_'], $result->tables);
        $this->assertEquals(['PRIMARY KEY', 'IDX_127C71CEC0EB25A3'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(2, $result->steps);

        $this->assertEquals(QueryType::PRIMARY, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY KEY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);

        $this->assertEquals(QueryType::INDEX, $result->steps[1]->type);
        $this->assertEquals('foreign_', $result->steps[1]->table);
        $this->assertEquals('IDX_127C71CEC0EB25A3', $result->steps[1]->index);
        $this->assertFalse($result->steps[1]->covering);
        $this->assertFalse($result->steps[1]->temporary);
    }

    public function test_explain_covering_index_query()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain(TestEmbeddedEntity::where('name', 'toto')->select(['id', 'name'])->toRawSql());

        $this->assertEquals(QueryType::INDEX, $result->type);
        $this->assertEquals(['foreign_'], $result->tables);
        $this->assertEquals(['IDX_127C71CEC0EB25A3'], $result->indexes);
        $this->assertTrue($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::INDEX, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertEquals('IDX_127C71CEC0EB25A3', $result->steps[0]->index);
        $this->assertTrue($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_covering_scan_query()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain(TestEmbeddedEntity::select(['id', 'name'])->toRawSql());

        $this->assertEquals(QueryType::SCAN, $result->type);
        $this->assertEquals(['foreign_'], $result->tables);
        $this->assertEquals(['id_name'], $result->indexes);
        $this->assertTrue($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::SCAN, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertEquals('id_name', $result->steps[0]->index);
        $this->assertTrue($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_fk_query()
    {
        TestEntity::repository()->schema()->migrate();
        TestEmbeddedEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain(TestEntity::where('foreign.city', 'toto')->toRawSql());

        $this->assertEquals(QueryType::SCAN, $result->type);
        $this->assertEquals(['test_', 'foreign_'], $result->tables);
        $this->assertEquals(['PRIMARY KEY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(2, $result->steps);

        $this->assertEquals(QueryType::SCAN, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertNull($result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);

        $this->assertEquals(QueryType::PRIMARY, $result->steps[1]->type);
        $this->assertEquals('foreign_', $result->steps[1]->table);
        $this->assertEquals('PRIMARY KEY', $result->steps[1]->index);
        $this->assertFalse($result->steps[1]->covering);
        $this->assertFalse($result->steps[1]->temporary);
    }

    public function test_explain_pk_and_filter_query()
    {
        TestEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain(TestEntity::where('id', 42)->where('name', 'toto')->toRawSql());

        $this->assertEquals(QueryType::PRIMARY, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEquals(['PRIMARY KEY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::PRIMARY, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY KEY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_pk_composite()
    {
        CompositePkEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain(CompositePkEntity::where('key1', 'toto')->where('key2', 'tata')->toRawSql());

        $this->assertEquals(QueryType::INDEX, $result->type);
        $this->assertEquals(['_composite_pk'], $result->tables);
        $this->assertEquals(['sqlite_autoindex__composite_pk_1'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::INDEX, $result->steps[0]->type);
        $this->assertEquals('_composite_pk', $result->steps[0]->table);
        $this->assertEquals('sqlite_autoindex__composite_pk_1', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_order_using_b_tree()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain(TestEmbeddedEntity::where('id', [12, 45, 96])->order('name')->toRawSql());

        $this->assertEquals(QueryType::PRIMARY, $result->type);
        $this->assertEquals(['foreign_'], $result->tables);
        $this->assertEquals(['PRIMARY KEY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertTrue($result->temporary);
        $this->assertCount(2, $result->steps);

        $this->assertEquals(QueryType::PRIMARY, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY KEY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);

        $this->assertEquals(QueryType::UNDEFINED, $result->steps[1]->type);
        $this->assertNull($result->steps[1]->table);
        $this->assertNull($result->steps[1]->index);
        $this->assertFalse($result->steps[1]->covering);
        $this->assertTrue($result->steps[1]->temporary);
    }

    public function test_explain_order_using_index()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain(TestEmbeddedEntity::order('name')->toRawSql());

        $this->assertEquals(QueryType::SCAN, $result->type);
        $this->assertEquals(['foreign_'], $result->tables);
        $this->assertEquals(['IDX_127C71CEC0EB25A3'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);

        $this->assertEquals(QueryType::SCAN, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertEquals('IDX_127C71CEC0EB25A3', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_with_automatic_index()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.city = t0.city WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city');

        $this->assertEquals(QueryType::SCAN, $result->type);
        $this->assertEquals(['foreign_', 'foreign_'], $result->tables);
        $this->assertEquals([], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertTrue($result->temporary);
        $this->assertCount(3, $result->steps);

        $this->assertEquals(QueryType::SCAN, $result->steps[0]->type);
        $this->assertEquals('foreign_', $result->steps[0]->table);
        $this->assertNull($result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);

        $this->assertEquals(QueryType::SCAN, $result->steps[1]->type);
        $this->assertEquals('foreign_', $result->steps[1]->table);
        $this->assertNull($result->steps[1]->index);
        $this->assertFalse($result->steps[1]->covering);
        $this->assertFalse($result->steps[1]->temporary);

        $this->assertEquals(QueryType::UNDEFINED, $result->steps[2]->type);
        $this->assertNull($result->steps[2]->table);
        $this->assertNull($result->steps[2]->index);
        $this->assertFalse($result->steps[2]->covering);
        $this->assertTrue($result->steps[2]->temporary);
    }

    public function test_explain_subquery()
    {
        TestEntity::repository()->schema()->migrate();
        TestEmbeddedEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain(
            TestEntity::where(
                'foreign.id',
                ':in',
                TestEmbeddedEntity::repository()->queries()->fromAlias('emb')->where('name', 'toto')->select('id')
            )
                ->toRawSql()
        );

        $this->assertEquals(QueryType::SCAN, $result->type);
        $this->assertEquals(['test_', 'foreign_'], $result->tables);
        $this->assertEquals(['IDX_127C71CEC0EB25A3'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(2, $result->steps);

        $this->assertEquals(QueryType::SCAN, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertNull($result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);

        $this->assertEquals(QueryType::INDEX, $result->steps[1]->type);
        $this->assertEquals('foreign_', $result->steps[1]->table);
        $this->assertEquals('IDX_127C71CEC0EB25A3', $result->steps[1]->index);
        $this->assertTrue($result->steps[1]->covering);
        $this->assertFalse($result->steps[1]->temporary);
    }

    public function test_explain_update()
    {
        TestEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain('UPDATE test_ SET name = "toto" WHERE id = 42');

        $this->assertEquals(QueryType::PRIMARY, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEquals(['PRIMARY KEY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::PRIMARY, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY KEY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_delete()
    {
        TestEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain('DELETE FROM test_ WHERE id = 42');

        $this->assertEquals(QueryType::PRIMARY, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEquals(['PRIMARY KEY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::PRIMARY, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY KEY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_ignore_queries()
    {
        TestEntity::repository()->schema()->migrate();

        $this->assertNull($this->explainer->explain('EXPLAIN SELECT * FROM test_ SET name = "toto" WHERE id = 42'));
        $this->assertNull($this->explainer->explain('INSERT INTO test_(id, name) VALUES (42, "toto")'));
    }

    public function test_explain_use_parameters()
    {
        CompositePkEntity::repository()->schema()->migrate();

        $query = CompositePkEntity::where('key1', 'toto')->where('key2', 'tata');
        $query->compile();

        $result = $this->explainer->explain($query->toSql(), [1 => 'toto', 2 => 'tata']);

        $this->assertEquals(QueryType::INDEX, $result->type);
        $this->assertEquals(['_composite_pk'], $result->tables);
        $this->assertEquals(['sqlite_autoindex__composite_pk_1'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::INDEX, $result->steps[0]->type);
        $this->assertEquals('_composite_pk', $result->steps[0]->table);
        $this->assertEquals('sqlite_autoindex__composite_pk_1', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }

    public function test_explain_use_typed_parameters()
    {
        TestEntity::repository()->schema()->migrate();

        $result = $this->explainer->explain(
            'SELECT t0.* FROM test_ t0 WHERE t0.id > ? ORDER BY t0.id LIMIT ? OFFSET ?',
            [1 => 42, 2 => 5, 3 => 15]
        );

        $this->assertEquals(QueryType::PRIMARY, $result->type);
        $this->assertEquals(['test_'], $result->tables);
        $this->assertEquals(['PRIMARY KEY'], $result->indexes);
        $this->assertFalse($result->covering);
        $this->assertFalse($result->temporary);
        $this->assertCount(1, $result->steps);
        $this->assertEquals(QueryType::PRIMARY, $result->steps[0]->type);
        $this->assertEquals('test_', $result->steps[0]->table);
        $this->assertEquals('PRIMARY KEY', $result->steps[0]->index);
        $this->assertFalse($result->steps[0]->covering);
        $this->assertFalse($result->steps[0]->temporary);
    }
}
