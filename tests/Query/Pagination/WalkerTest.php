<?php

namespace Query\Pagination;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Pagination\Walker;
use Bdf\Prime\Query\Pagination\WalkStrategy\KeyWalkStrategy;
use Bdf\Prime\Query\Pagination\WalkStrategy\MapperPrimaryKey;
use Bdf\Prime\Query\Pagination\WalkStrategy\PaginationWalkStrategy;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\TestEntity;
use Doctrine\DBAL\Logging\DebugStack;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Class WalkerTest
 */
class WalkerTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var DebugStack
     */
    private $queries;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();
        $this->prime()->connection('test')->getConfiguration()->setSQLLogger($this->queries = new DebugStack());
        TestPack::pack()->declareEntity(TestEntity::class);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }

    public function paginationStrategy()
    {
        return new PaginationWalkStrategy();
    }

    public function keyStrategy()
    {
        return new KeyWalkStrategy(new MapperPrimaryKey(TestEntity::mapper()));
    }

    public function provideStrategies()
    {
        return [
            ['paginationStrategy'],
            ['keyStrategy'],
        ];
    }

    /**
     * @dataProvider provideStrategies
     */
    public function test_iterator_single_chunk($strategy)
    {
        $entities = $this->insertEntities(15);

        $walker = new Walker(TestEntity::builder());
        $walker->setStrategy($this->$strategy());

        $this->assertEquals($entities, iterator_to_array($walker));
        $this->assertCount(2, $this->queries->queries); // count + select
    }

    /**
     * @dataProvider provideStrategies
     */
    public function test_iterator_single_multiple_chunks($strategy)
    {
        $entities = $this->insertEntities(15);

        $walker = new Walker(TestEntity::builder(), 3);
        $walker->setStrategy($this->$strategy());

        $this->assertEquals($entities, iterator_to_array($walker));
        $this->assertCount(6, $this->queries->queries); // count + 5 select
    }

    /**
     * @dataProvider provideStrategies
     */
    public function test_iterator_with_criteria($strategy)
    {
        $entities = $this->insertEntities(15);

        $walker = new Walker(TestEntity::builder()->where('id', ':between', [4, 8]), 3);
        $walker->setStrategy($this->$strategy());

        $actual = iterator_to_array($walker);
        $this->assertEquals(array_slice($entities, 3, 5), $actual);
        $this->assertCount(3, $this->queries->queries); // count + 2 select
    }

    /**
     * @dataProvider provideStrategies
     */
    public function test_iterator_with_order($strategy)
    {
        $entities = $this->insertEntities(15);

        $walker = new Walker(TestEntity::builder()->order('id', 'DESC'), 3);
        $walker->setStrategy($this->$strategy());

        $actual = iterator_to_array(new Walker(TestEntity::builder()->order('id', 'DESC')));
        $this->assertEquals(array_reverse($entities), $actual);
    }

    /**
     * @dataProvider provideStrategies
     */
    public function test_iterator_with_projection($strategy)
    {
        $entities = $this->insertEntities(15);

        $walker = new Walker(TestEntity::builder()->select(['id', 'name']), 3);
        $walker->setStrategy($this->$strategy());

        $actual = iterator_to_array(new Walker(TestEntity::builder()->order('id', 'DESC')->select(['id', 'name'])));
        $this->assertEquals(array_reverse($entities), $actual);
    }

    /**
     * @dataProvider provideStrategies
     */
    public function test_getters($strategy)
    {
        $entities = $this->insertEntities(15);

        $walker = new Walker(TestEntity::builder(), 3);
        $walker->setStrategy($this->$strategy());
        $walker->load();

        $this->assertEquals(3, $walker->count());
        $this->assertEquals(1, $walker->page());
        $this->assertEquals(3, $walker->limit());
        $this->assertEquals(3, $walker->pageMaxRows());
        $this->assertEquals(15, $walker->size());
    }

    /**
     *
     */
    public function test_iterator_with_delete_during_walk_with_key_strategy()
    {
        $entities = $this->insertEntities(15);
        $actual = [];

        $walker = new Walker(TestEntity::builder(), 3);
        $walker->setStrategy(new KeyWalkStrategy(new MapperPrimaryKey(TestEntity::mapper())));

        foreach ($walker as $entity) {
            $actual[] = $entity;
            $entity->delete();
        }

        $this->assertEquals($entities, $actual);
    }

    /**
     *
     */
    public function test_unsupported_order_for_key_walk_strategy()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('KeyWalkStrategy is not supported by this query');

        $walker = new Walker(TestEntity::builder()->order('name'), 3);
        $walker->setStrategy(new KeyWalkStrategy(new MapperPrimaryKey(TestEntity::mapper())));

        iterator_to_array($walker);
    }

    /**
     *
     */
    public function test_unsupported_query_type_for_key_walk_strategy()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('KeyWalkStrategy is not supported by this query');

        $walker = new Walker(TestEntity::keyValue(), 3);
        $walker->setStrategy(new KeyWalkStrategy(new MapperPrimaryKey(TestEntity::mapper())));

        iterator_to_array($walker);
    }

    /**
     *
     */
    public function test_unsupported_page_for_key_walk_strategy()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('KeyWalkStrategy is not supported by this query');

        $walker = new Walker(TestEntity::builder(), 3, 3);
        $walker->setStrategy(new KeyWalkStrategy(new MapperPrimaryKey(TestEntity::mapper())));

        iterator_to_array($walker);
    }

    /**
     *
     */
    public function test_unsupported_missing_projection_key_for_key_walk_strategy()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('KeyWalkStrategy is not supported by this query');

        $walker = new Walker(TestEntity::builder()->select('name', 'foreign'));
        $walker->setStrategy(new KeyWalkStrategy(new MapperPrimaryKey(TestEntity::mapper())));

        iterator_to_array($walker);
    }

    /**
     *
     */
    public function test_key_walk_strategy_with_simple_group_by()
    {
        $entities = [
            new TestEntity(['name' => 'foo1']), // overridden
            new TestEntity(['name' => 'foo2']),
            new TestEntity(['name' => 'foo1']),
            new TestEntity(['name' => 'foo1']), // overridden, use as cursor
            new TestEntity(['name' => 'foo3']),
            new TestEntity(['name' => 'foo4']),
            new TestEntity(['name' => 'foo5']),
        ];

        foreach ($entities as $entity) {
            $entity->insert();
        }

        $walker = new Walker(TestEntity::builder()->by('name'), 4);
        $walker->setStrategy(new KeyWalkStrategy(new MapperPrimaryKey(TestEntity::mapper())));

        $this->assertEquals([$entities[3], $entities[1], $entities[4], $entities[5], $entities[6]], iterator_to_array($walker, false));

        $walker = new Walker(TestEntity::builder()->by('name'), 4);
        $walker->setStrategy(new KeyWalkStrategy(new MapperPrimaryKey(TestEntity::mapper())));

        $this->assertEquals([
            'foo1' => $entities[3],
            'foo2' => $entities[1],
            'foo3' => $entities[4],
            'foo4' => $entities[5],
            'foo5' => $entities[6],
        ], iterator_to_array($walker));
    }

    /**
     *
     */
    public function test_key_walk_strategy_with_group_by_combine()
    {
        $entities = [
            new TestEntity(['name' => 'foo1']),
            new TestEntity(['name' => 'foo2']),
            new TestEntity(['name' => 'foo1']),
            new TestEntity(['name' => 'foo1']),
            new TestEntity(['name' => 'foo3']),
            new TestEntity(['name' => 'foo4']),
            new TestEntity(['name' => 'foo5']),
        ];

        foreach ($entities as $entity) {
            $entity->insert();
        }

        $walker = new Walker(TestEntity::builder()->by('name', true), 4);
        $walker->setStrategy(new KeyWalkStrategy(new MapperPrimaryKey(TestEntity::mapper())));

        $this->assertEquals([
            'foo1' => [$entities[0], $entities[2], $entities[3]],
            'foo2' => [$entities[1]],
            'foo3' => [$entities[4]],
            'foo4' => [$entities[5]],
            'foo5' => [$entities[6]],
        ], iterator_to_array($walker));
    }

    /**
     *
     */
    public function test_key_walk_strategy_should_replace_key_clause()
    {
        $entities = [
            new TestEntity(['name' => 'foo1']),
            new TestEntity(['name' => 'foo2']),
            new TestEntity(['name' => 'foo1']),
            new TestEntity(['name' => 'foo1']),
            new TestEntity(['name' => 'foo3']),
            new TestEntity(['name' => 'foo4']),
            new TestEntity(['name' => 'foo5']),
        ];

        foreach ($entities as $entity) {
            $entity->insert();
        }

        $this->queries->queries = [];

        $walker = new Walker(TestEntity::builder(), 4);
        $walker->setStrategy(new KeyWalkStrategy(new MapperPrimaryKey(TestEntity::mapper())));

        $this->assertCount(7, iterator_to_array($walker));

        $queries = array_values($this->queries->queries);
        $this->assertCount(3, $queries);

        $this->assertSame('SELECT t0.* FROM test_ t0 WHERE t0.id > ? ORDER BY t0.id ASC LIMIT 4', $queries[1]['sql']);
        $this->assertSame([4], $queries[1]['params']);
        $this->assertSame('SELECT t0.* FROM test_ t0 WHERE t0.id > ? ORDER BY t0.id ASC LIMIT 4', $queries[2]['sql']);
        $this->assertSame([7], $queries[2]['params']);

        $this->queries->queries = [];

        $walker = new Walker(TestEntity::builder()->where('name', '>', 'foo1'), 2);
        $walker->setStrategy(new KeyWalkStrategy(new MapperPrimaryKey(TestEntity::mapper())));

        $this->assertCount(4, iterator_to_array($walker));

        $queries = array_values($this->queries->queries);
        $this->assertCount(3, $queries);

        $this->assertSame('SELECT t0.* FROM test_ t0 WHERE t0.name > ? AND t0.id > ? ORDER BY t0.id ASC LIMIT 2', $queries[1]['sql']);
        $this->assertSame(['foo1', 5], $queries[1]['params']);
        $this->assertSame('SELECT t0.* FROM test_ t0 WHERE t0.name > ? AND t0.id > ? ORDER BY t0.id ASC LIMIT 2', $queries[2]['sql']);
        $this->assertSame(['foo1', 7], $queries[2]['params']);

        $this->queries->queries = [];

        $walker = new Walker(TestEntity::builder()->where('id', '<', 5), 2);
        $walker->setStrategy(new KeyWalkStrategy(new MapperPrimaryKey(TestEntity::mapper())));

        $this->assertCount(4, iterator_to_array($walker));

        $queries = array_values($this->queries->queries);
        $this->assertCount(3, $queries);

        $this->assertSame('SELECT t0.* FROM test_ t0 WHERE t0.id < ? ORDER BY t0.id ASC LIMIT 2', $queries[0]['sql']);
        $this->assertSame([5], $queries[0]['params']);
        $this->assertSame('SELECT t0.* FROM test_ t0 WHERE t0.id < ? AND t0.id > ? ORDER BY t0.id ASC LIMIT 2', $queries[1]['sql']);
        $this->assertSame([5, 2], $queries[1]['params']);
        $this->assertSame('SELECT t0.* FROM test_ t0 WHERE t0.id < ? AND t0.id > ? ORDER BY t0.id ASC LIMIT 2', $queries[2]['sql']);
        $this->assertSame([5, 4], $queries[2]['params']);


        $walker = new Walker(TestEntity::builder()->whereRaw('MOD(id, 2) = 0'), 2);
        $walker->setStrategy(new KeyWalkStrategy(new MapperPrimaryKey(TestEntity::mapper())));
        $this->assertCount(3, iterator_to_array($walker));
    }

    private function insertEntities(int $count): array
    {
        $entities = [];

        for ($i = 0; $i < $count; ++$i) {
            $entity = new TestEntity(['name' => 'entity '.$i]);
            $entity->insert();

            $entities[] = $entity;
        }

        $this->queries->queries = [];

        return $entities;
    }
}
