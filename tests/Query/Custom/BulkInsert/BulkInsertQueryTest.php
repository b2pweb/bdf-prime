<?php

namespace Bdf\Prime\Query\Custom\BulkInsert;

use Bdf\Prime\Cache\ArrayCache;
use Bdf\Prime\Cache\CacheKey;
use Bdf\Prime\Cache\DoctrineCacheAdapter;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Builder\TypesHelperTableBuilder;
use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class BulkInsertQueryTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var SimpleConnection
     */
    private $connection;


    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->connection = $this->prime()->connection('test');

        $this->connection->schema()
            ->table('person', function (TypesHelperTableBuilder $builder) {
                $builder->integer('id')->autoincrement();
                $builder->string('first_name');
                $builder->string('last_name');
                $builder->integer('age')->nillable();
                $builder->dateTime('birthday')->nillable();
            })
        ;
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->connection->schema()->drop('person');
        $this->unsetPrime();
    }

    /**
     *
     */
    public function test_simple_insert()
    {
        $count = $this->query()
            ->values([
                'first_name' => 'John',
                'last_name'  => 'Doe',
            ])
            ->execute()
        ;

        $this->assertEquals(1, $count);

        $this->assertEquals([
            [
                'id'         => 1,
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'age'        => null,
                'birthday'   => null,
            ]
        ], $this->connection->from('person')->all());
    }

    /**
     *
     */
    public function test_bulk()
    {
        $count = $this->query()
            ->bulk()
            ->values([
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ])
            ->values([
                'first_name' => 'Mickey',
                'last_name'  => 'Mouse'
            ])
            ->execute()
        ;

        $this->assertEquals(2, $count);
        $this->assertEquals([
            [
                'id'         => 1,
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'age'        => null,
                'birthday'   => null,
            ],
            [
                'id'         => 2,
                'first_name' => 'Mickey',
                'last_name'  => 'Mouse',
                'age'        => null,
                'birthday'   => null,
            ],
        ], $this->connection->from('person')->all());
    }

    /**
     *
     */
    public function test_replace()
    {
        $this->query()
            ->values([
                'id'         => 1,
                'first_name' => 'John',
                'last_name'  => 'Doe',
            ])
            ->execute()
        ;

        $count = $this->query()
            ->replace()
            ->values([
                'id'         => 1,
                'first_name' => 'John',
                'last_name'  => 'Smith',
            ])
            ->execute()
        ;

        $this->assertEquals(1, $count);
        $this->assertEquals([
            [
                'id'         => 1,
                'first_name' => 'John',
                'last_name'  => 'Smith',
                'age'        => null,
                'birthday'   => null,
            ]
        ], $this->connection->from('person')->all());
    }

    /**
     *
     */
    public function test_ignore()
    {
        $this->query()
            ->values([
                'id'         => 1,
                'first_name' => 'John',
                'last_name'  => 'Doe',
            ])
            ->execute()
        ;

        $count = $this->query()
            ->ignore()
            ->values([
                'id'         => 1,
                'first_name' => 'John',
                'last_name'  => 'Smith',
            ])
            ->execute()
        ;

        $this->assertEquals(0, $count);
        $this->assertEquals([
            [
                'id'         => 1,
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'age'        => null,
                'birthday'   => null,
            ]
        ], $this->connection->from('person')->all());
    }

    /**
     *
     */
    public function test_no_recompile_with_same_columns()
    {
        $query = $this->query()
            ->values([
                'first_name' => 'John',
                'last_name'  => 'Doe',
            ])
        ;

        $compiled = $query->compile();
        $query->execute();

        $query
            ->values([
                'first_name' => 'Mickey',
                'last_name'  => 'Mouse',
            ])
            ->execute()
        ;

        $this->assertSame($compiled, $query->compile());

        $this->assertEquals([
            [
                'id'         => 1,
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'age'        => null,
                'birthday'   => null,
            ],
            [
                'id'         => 2,
                'first_name' => 'Mickey',
                'last_name'  => 'Mouse',
                'age'        => null,
                'birthday'   => null,
            ]
        ], $this->connection->from('person')->all());
    }

    /**
     *
     */
    public function test_recompile_when_column_changed()
    {
        $query = $this->query()
            ->columns(['first_name', 'last_name'])
            ->values([
                'first_name' => 'John',
                'last_name'  => 'Doe',
            ])
        ;

        $compiled = $query->compile();

        $query->columns(['first_name', 'last_name', 'age']);
        $this->assertNotSame($compiled, $query->compile());
    }

    /**
     *
     */
    public function test_recompile_on_bulk()
    {
        $query = $this->query()
            ->bulk()
            ->values([
                'first_name' => 'John',
                'last_name'  => 'Doe',
            ])
        ;

        $compiled = $query->compile();

        $query->values([
            'first_name' => 'Mickey',
            'last_name'  => 'Mouse',
        ]);

        $this->assertNotSame($compiled, $query->compile());
    }

    /**
     *
     */
    public function test_orm()
    {
        $this->pack()->declareEntity(TestEntity::class);

        /** @var BulkInsertQuery $query */
        $query = TestEntity::repository()->queries()->make(BulkInsertQuery::class);
        $query
            ->values([
                'name'       => 'new entity',
                'foreign.id' => 15,
            ])
            ->execute()
        ;

        $this->assertEquals([
            new TestEntity([
                'id'   => 1,
                'name' => 'new entity',
                'foreign' => new TestEmbeddedEntity(['id' => 15])
            ])
        ], TestEntity::all());
    }

    /**
     *
     */
    public function test_insert_will_clear_cache()
    {
        $cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ArrayCache());
        $key = new CacheKey('test:person', 'foo');
        $cache->set($key, 'bar');
        $this->assertSame('bar', $cache->get($key));

        $query = $this->query();
        $query->setCache($cache);
        $query->values([
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);

        $this->assertEquals(1, $query->execute());
        $this->assertNull($cache->get($key));
    }

    /**
     *
     */
    public function test_orm_cache()
    {
        Prime::service()->mappers()->setResultCache($cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ArrayCache()));

        $this->pack()->declareEntity(TestEntity::class);

        $this->assertSame(0, TestEntity::repository()->count());
        $this->assertSame([['aggregate' => '0']], $cache->get(new CacheKey('test:test_', sha1('SELECT COUNT(*) AS aggregate FROM test_ t0-a:0:{}'))));

        /** @var BulkInsertQuery $query */
        $query = TestEntity::repository()->queries()->make(BulkInsertQuery::class);
        $query
            ->values([
                'name'       => 'new entity',
                'foreign.id' => 15,
            ])
            ->execute()
        ;

        $this->assertNull($cache->get(new CacheKey('test:test_', sha1('SELECT COUNT(*) AS aggregate FROM test_ t0-a:0:{}'))));
        $this->assertSame(1, TestEntity::repository()->count());
    }

    /**
     * @return BulkInsertQuery
     */
    private function query()
    {
        return (new BulkInsertQuery($this->connection))->into('person');
    }
}
