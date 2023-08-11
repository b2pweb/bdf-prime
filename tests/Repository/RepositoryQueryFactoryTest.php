<?php

namespace Bdf\Prime\Repository;

use Bdf\Prime\Cache\ArrayCache;
use Bdf\Prime\CompositePkEntity;
use Bdf\Prime\Customer;
use Bdf\Prime\CustomerPack;
use Bdf\Prime\Exception\EntityNotFoundException;
use Bdf\Prime\Exception\QueryBuildingException;
use Bdf\Prime\Faction;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Compiler\Preprocessor\OrmPreprocessor;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\Pagination\Walker;
use Bdf\Prime\Query\Pagination\WalkStrategy\KeyWalkStrategy;
use Bdf\Prime\Query\Pagination\WalkStrategy\PaginationWalkStrategy;
use Bdf\Prime\Query\Query;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Test\RepositoryAssertion;
use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class RepositoryQueryFactoryTest extends TestCase
{
    use PrimeTestCase;
    use RepositoryAssertion;

    /**
     * @var RepositoryQueryFactory
     */
    private $factory;

    /**
     * @var ArrayCache
     */
    protected $cache;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->factory = new RepositoryQueryFactory(
            $this->repository = TestEntity::repository(),
            $this->cache = new ArrayCache()
        );
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack
            ->declareEntity('Bdf\Prime\Faction')
            ->persist([
                'entity' => new TestEntity([
                    'id'         => 1,
                    'name'       => 'Entity',
                    'foreign'    => new TestEmbeddedEntity(['id' => 1]),
                    'dateInsert' => new \DateTime(),
                ]),
                'embedded' => new TestEmbeddedEntity([
                    'id'        => 1,
                    'name'      => 'Embedded',
                    'city'      => 'City',
                ]),
            ]);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
    }

    /**
     *
     */
    public function test_make()
    {
        $query = $this->factory->make(Query::class);

        $this->assertInstanceOf(Query::class, $query);
        $this->assertSame($this->repository, $query->repository());
        $this->assertSame($this->cache, $query->cache());
        $this->assertEquals('test_', $query->statements['tables']['test_']['table']);
        $this->assertEquals(new OrmPreprocessor($this->repository), $query->preprocessor());

        $this->assertInstanceOf(KeyValueQuery::class, $this->factory->make(KeyValueQuery::class));
    }

    /**
     *
     */
    public function test_builder()
    {
        $this->assertInstanceOf(Query::class, $this->factory->builder());
        $this->assertEquals(new OrmPreprocessor($this->repository), $this->factory->builder()->preprocessor());
    }

    /**
     *
     */
    public function test_builder_with_allow_unknown_attribute()
    {
        $this->repository->mapper()->setAllowUnknownAttribute(false);
        $this->assertFalse($this->factory->builder()->isAllowUnknownAttribute());

        $this->repository->mapper()->setAllowUnknownAttribute(true);
        $this->assertTrue($this->factory->builder()->isAllowUnknownAttribute());
    }

    /**
     *
     */
    public function test_fromAlias()
    {
        $query = $this->factory->fromAlias('alias');

        $this->assertInstanceOf(Query::class, $query);
        $this->assertEquals(new OrmPreprocessor($this->repository), $query->preprocessor());
        $this->assertEquals('SELECT alias.* FROM test_ alias', $query->toSql());
    }

    /**
     *
     */
    public function test_findById()
    {
        $this->assertEntity($this->pack()->get('entity'), $this->factory->findById(1));
        $this->assertEntity($this->pack()->get('entity'), $this->factory->findById(['id' => 1]));
        $this->assertNull($this->factory->findById('not_found'));
        $this->assertNull($this->factory->findById(['id' => 'not_found']));
    }

    /**
     *
     */
    public function test_findById_composite_key()
    {
        $this->pack()->nonPersist([
            'pack1' => new CustomerPack([
                'customerId' => '123',
                'packId' => 456,
            ]),
            'pack2' => new CustomerPack([
                'customerId' => '321',
                'packId' => 456,
            ]),
        ]);

        $factory = new RepositoryQueryFactory(CustomerPack::repository());

        $this->assertEntity($this->pack()->get('pack1'), $factory->findById(['customerId' => '123', 'packId' => 456]));
        $this->assertEntity($this->pack()->get('pack2'), $factory->findById(['customerId' => '321', 'packId' => 456]));
        $this->assertEntity($this->pack()->get('pack2'), $factory->findById(['packId' => 456, 'customerId' => '321']));
        $this->assertNull($factory->findById(['customerId' => '321', 'packId' => 654]));

        try {
            $factory->findById(['badKey' => '321', 'packId' => 654]);
            $this->fail('Expect QueryException');
        } catch (QueryBuildingException $e) {
            $this->assertEquals('Only primary keys must be passed to findById()', $e->getMessage());
        }

        try {
            $factory->findById(['customerId' => '321']);
            $this->fail('Expect QueryException');
        } catch (QueryBuildingException $e) {
            $this->assertEquals('Only primary keys must be passed to findById()', $e->getMessage());
        }
    }

    /**
     *
     */
    public function test_findById_bad_keys()
    {
        $this->expectException(QueryBuildingException::class);
        $this->expectExceptionMessage('Only primary keys must be passed to findById()');

        $this->factory->findById(['foreign' => 1]);
    }

    /**
     *
     */
    public function test_get()
    {
        $this->assertEntity($this->pack()->get('entity'), $this->factory->get(1));
        $this->assertEntity($this->pack()->get('entity'), $this->factory->get(['name' => 'Entity']));
        $this->assertEquals(new TestEntity(['name' => 'Entity']), $this->factory->get(1, ['name']));
        $this->assertNull($this->factory->get('not_found'));
    }

    /**
     *
     */
    public function test_getOrFail_success()
    {
        $this->assertEntity($this->pack()->get('entity'), $this->factory->getOrFail(1));
    }

    /**
     *
     */
    public function test_getOrFail_not_found()
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Cannot resolve entity identifier "not_found"');

        $this->factory->getOrFail('not_found');
    }

    /**
     *
     */
    public function test_getOrNew()
    {
        $this->assertEntity($this->pack()->get('entity'), $this->factory->getOrNew(1));
        $this->assertEquals(new TestEntity(), $this->factory->getOrNew('not_found'));
    }

    /**
     *
     */
    public function test_keyValue()
    {
        $this->assertInstanceOf(KeyValueQuery::class, $this->factory->keyValue());
        $this->assertEquals('SELECT * FROM test_', $this->factory->keyValue()->toSql());
        $this->assertEquals('SELECT * FROM test_ WHERE id = ?', $this->factory->keyValue('id', 1)->toSql());
        $this->assertEquals('SELECT * FROM test_ WHERE id = ? AND name = ?', $this->factory->keyValue(['id' => 1, 'name' => 'Entity'])->toSql());
    }

    /**
     *
     */
    public function test_magic_call_custom_query()
    {
        $query = $this->factory->testQuery(5);

        $this->assertInstanceOf(KeyValueQuery::class, $query);
        $this->assertEquals('SELECT * FROM test_ WHERE id = ? LIMIT 1', $query->toSql());
    }

    /**
     *
     */
    public function test_magic_call_foward_to_builder()
    {
        $query = $this->factory->where('nameLike', '%e%');

        $this->assertInstanceOf(Query::class, $query);
        $this->assertEquals('SELECT t0.* FROM test_ t0 WHERE t0.name LIKE ?', $query->toSql());
    }

    /**
     *
     */
    public function test_countKeyValue()
    {
        $this->assertEquals(1, $this->factory->countKeyValue('id', 1));
        $this->assertEquals(0, $this->factory->countKeyValue('id', 5));
        $this->assertEquals(1, $this->factory->countKeyValue(['id' => 1]));
        $this->assertEquals(1, $this->factory->countKeyValue(['id' => 1, 'name' => 'Entity']));
    }

    /**
     * Bug #FRAM-74
     */
    public function test_countKeyValue_side_effect()
    {
        $this->getTestPack()->nonPersist(new TestEntity(['id' => 2, 'name' => 'Foo']));

        $this->assertEquals(1, $this->factory->countKeyValue('id', 1));
        $this->assertEquals(1, $this->factory->countKeyValue('name', 'Foo'));
        $this->assertEquals(1, $this->factory->countKeyValue('id', 1));
        $this->assertEquals(2, $this->factory->countKeyValue());
    }

    /**
     *
     */
    public function test_keyValue_not_supported()
    {
        $this->pack()->nonPersist($faction = new Faction([
            'id'      => '5',
            'name'    => 'test',
            'enabled' => true,
        ]));

        $factory = new RepositoryQueryFactory(Faction::repository());

        $this->assertNull($factory->keyValue());
        $this->assertEntity($faction, $factory->findById(5));
        $this->assertEquals(1, $factory->countKeyValue());
    }

    /**
     *
     */
    public function test_entities()
    {
        $this->pack()
            ->nonPersist([
                'customer' => new Customer([
                    'id'            => '123',
                    'name'          => 'Customer',
                ]),
                'customer2' => new Customer([
                    'id'            => '456',
                    'name'          => 'Customer 2',
                ]),
                'customer3' => new Customer([
                    'id'            => '789',
                    'name'          => 'Customer 3',
                ]),
            ]);

        $queries = new RepositoryQueryFactory(Customer::repository());
        $entities = [$this->pack()->get('customer'), $this->pack()->get('customer2')];

        $query = $queries->entities($entities);

        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertNotSame($entities, $query->all());
        $this->assertEntities($entities, $query->all());
    }

    /**
     *
     */
    public function test_entities_composite_pk()
    {
        $this->pack()->nonPersist([
            $c1 = new CompositePkEntity(['key1' => 'a', 'key2' => 'b']),
            $c2 = new CompositePkEntity(['key1' => 'b', 'key2' => 'c']),
            $c3 = new CompositePkEntity(['key1' => 'a', 'key2' => 'c']),
        ]);

        $entities = [$c1, $c2];

        $queries = new RepositoryQueryFactory(CompositePkEntity::repository());
        $query = $queries->entities($entities);

        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertNotSame($entities, $query->all());
        $this->assertEntities($entities, $query->all());
    }

    /**
     *
     */
    public function test_walk_strategy()
    {
        $walker = $this->factory->builder()->walk();

        $this->assertInstanceOf(Walker::class, $walker);
        $this->assertInstanceOf(KeyWalkStrategy::class, $walker->getStrategy());
        $walker->load();
        $this->assertEquals(['id' => 'ASC'], $walker->query()->getOrders());

        $walker = $this->factory->keyValue()->walk();

        $this->assertInstanceOf(Walker::class, $walker);
        $this->assertInstanceOf(PaginationWalkStrategy::class, $walker->getStrategy());

        $walker = $this->factory->builder()->order('name')->walk();

        $this->assertInstanceOf(Walker::class, $walker);
        $this->assertInstanceOf(PaginationWalkStrategy::class, $walker->getStrategy());

        $walker = $this->factory->builder()->order('id', 'desc')->walk();

        $this->assertInstanceOf(Walker::class, $walker);
        $this->assertInstanceOf(KeyWalkStrategy::class, $walker->getStrategy());
        $walker->load();
        $this->assertEquals(['id' => 'desc'], $walker->query()->getOrders());

        $walker = $this->factory->builder()->walk(10, 3);

        $this->assertInstanceOf(Walker::class, $walker);
        $this->assertInstanceOf(PaginationWalkStrategy::class, $walker->getStrategy());
    }

    /**
     *
     */
    public function test_walk_composite_pk()
    {
        $queries = new RepositoryQueryFactory(CompositePkEntity::repository());
        $walker = $queries->builder()->walk();

        $this->assertInstanceOf(Walker::class, $walker);
        $this->assertInstanceOf(PaginationWalkStrategy::class, $walker->getStrategy());
    }
}
