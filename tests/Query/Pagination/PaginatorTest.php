<?php

namespace Bdf\Prime\Query\Pagination;

use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Customer;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Query;
use Bdf\Prime\TestEntity;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class PaginatorTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([
            TestEntity::class,
            User::class,
            Customer::class
        ]);

        $pack->persist([
            new TestEntity(['name' => 'TEST1',]),
            new TestEntity(['name' => 'TEST2',]),
            new TestEntity(['name' => 'TEST3',]),
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
    public function test_pagination_wrapper()
    {
        $wrapper = Prime::repository(TestEntity::class)
            ->paginate(1);
        
        $this->assertInstanceOf(Paginator::class, $wrapper);
    }

    /**
     *
     */
    public function test_pagination_wrap_in_collection_interface()
    {
        $wrapper = Prime::repository(TestEntity::class)
            ->paginate(1);

        $this->assertInstanceOf(CollectionInterface::class, $wrapper->getIterator());
    }

    /**
     *
     */
    public function test_pagination_use_current_collection_interface()
    {
        $wrapper = Prime::repository(TestEntity::class)
            ->wrapAs('array')
            ->paginate(1);

        $this->assertInstanceOf(CollectionInterface::class, $wrapper->getIterator());
    }

    /**
     * 
     */
    public function test_pagination_is_auto_detected()
    {
        $wrapper = Prime::repository(TestEntity::class)
            ->find([':limitPage' => [1, 2]]);
        
        $this->assertInstanceOf(Paginator::class, $wrapper);
    }
    
    /**
     * 
     */
    public function test_limit_query_is_not_detected_as_pagination()
    {
        $wrapper = Prime::repository(TestEntity::class)
            ->find([':limit' => 1]);
        
        $this->assertTrue(is_array($wrapper), 'should be an array');
    }
    
    /**
     * 
     */
    public function test_walker_wrapper()
    {
        $wrapper = Prime::repository(TestEntity::class)->walk([]);
        
        $this->assertInstanceOf('Bdf\Prime\Query\Pagination\Walker', $wrapper);
    }
    
    /**
     * 
     */
    public function test_pagination_size()
    {
        $repository = Prime::repository(TestEntity::class);
        
        $wrapper = $repository
            ->paginate(1);
        
        $this->assertEquals(1, $wrapper->count(), 'wrapper count');
        $this->assertEquals(3, $wrapper->size(), 'wrapper size');
    }
    
    /**
     * 
     */
    public function test_walker_size()
    {
        $wrapper = Prime::repository(TestEntity::class)->walk(1);
        
        $this->assertEquals(0, $wrapper->count(), 'wrapper count should not be loaded');
        $this->assertEquals(3, $wrapper->size(), 'wrapper size');
    }
    
    /**
     * 
     */
    public function test_walk_over_all_collection()
    {
        $wrapper = Prime::repository(TestEntity::class)->walk(1);
        
        $i = 0;
        foreach ($wrapper as $key => $entity) {
            $this->assertEquals($i, $key);
            $i++;
            $this->assertEquals('TEST'.$i, $entity->name);
        }
        
        $this->assertEquals(3, $i, 'should iterate 3');
    }

    /**
     *
     */
    public function test_walk_by()
    {
        $wrapper = TestEntity::repository()->by('name')->walk(1);

        $i = 0;
        foreach ($wrapper as $key => $entity) {
            $i++;
            $this->assertEquals('TEST' . $i, $key);
            $this->assertEquals('TEST'.$i, $entity->name);
        }

        $this->assertEquals(3, $i, 'should iterate 3');
    }

    /**
     * 
     */
    public function test_paginate_iterate_collection()
    {
        $wrapper = Prime::repository(TestEntity::class)
            ->paginate(2);
        
        $i = 0;
        foreach ($wrapper as $key => $entity) {
            $this->assertEquals($i, $key);
            $i++;
            $this->assertEquals('TEST'.$i, $entity->name);
        }
        
        $this->assertEquals(2, $i, 'should iterate 2');
    }
    
    /**
     * 
     */
    public function test_paginate_iterate_collection_object()
    {
        $wrapper = Prime::repository(TestEntity::class)
            ->wrapAs('array')
            ->paginate(2);
        
        $i = 0;
        foreach ($wrapper as $entity) {
            $i++;
            $this->assertEquals('TEST'.$i, $entity->name, 'expected name TEST'.$i);
        }
        
        $this->assertEquals(2, $i, 'should iterate 2');
    }
    
    /**
     * 
     */
    public function test_query()
    {
        $wrapper = Prime::repository(TestEntity::class)->paginate(1);
        
        $this->assertInstanceOf(Query::class, $wrapper->query(), 'wrapper query');
    }
    
    /**
     * 
     */
    public function test_collection()
    {
        $repository = Prime::repository(TestEntity::class);
        
        $wrapper = $repository->paginate(1);
        
        $this->assertEquals([new TestEntity(['id' => 1, 'name' => 'TEST1'])], $wrapper->collection()->all());
    }
    
    /**
     * 
     */
    public function test_collection_method_can_be_called()
    {
        $wrapper = Prime::repository(TestEntity::class)->wrapAs('array')->paginate(1);
        
        $this->assertEquals('TEST1', $wrapper->get(0)->name);
    }
    
    /**
     * 
     */
    public function test_pagination_limit_info()
    {
        $wrapper = Prime::repository(TestEntity::class)->paginate(5, 2);
        
        $this->assertEquals(2, $wrapper->page(), 'wrapper page');
        $this->assertEquals(5, $wrapper->limit(), 'wrapper limit');
        $this->assertEquals(5, $wrapper->pageMaxRows(), 'wrapper pageMaxRows');
        $this->assertEquals(5, $wrapper->offset(), 'wrapper offset');
    }
    
    /**
     * 
     */
    public function test_walker_limit_info()
    {
        $wrapper = Prime::repository(TestEntity::class)->walk(5, 2);
        $wrapper->load();
        
        $this->assertEquals(2, $wrapper->page(), 'wrapper page');
        $this->assertEquals(5, $wrapper->limit(), 'wrapper limit');
        $this->assertEquals(5, $wrapper->pageMaxRows(), 'wrapper pageMaxRows');
        $this->assertEquals(5, $wrapper->offset(), 'wrapper offset');
    }
    
    /**
     * 
     */
    public function test_pagination_order()
    {
        $wrapper = Prime::repository(TestEntity::class)
            ->order('dateInsert')
            ->paginate(1);
        
        $this->assertEquals(['dateInsert' => 'ASC'], $wrapper->order(), 'wrapper order');
        $this->assertEquals('ASC', $wrapper->order('dateInsert'), 'wrapper attribute order');
    }
    
    /**
     * 
     */
    public function test_order_when_empty()
    {
        $wrapper = Prime::repository(TestEntity::class)
            ->paginate(1);
        
        $this->assertEquals([], $wrapper->order(), 'wrapper order');
        $this->assertEquals(null, $wrapper->order('dateInsert'), 'wrapper attribute order');
    }
    
    /**
     * 
     */
    public function test_collection_method_with_walker()
    {
        $this->expectException('LogicException');
        
        $wrapper = Prime::repository(TestEntity::class)->walk();
        
        $wrapper->map(function(){});
    }
    
    /**
     * 
     */
    public function test_collection_supports_collection_methods()
    {
        $wrapper = Prime::repository(TestEntity::class)
            ->wrapAs('array')
            ->paginate(1);
        
        $this->assertFalse($wrapper->isEmpty());
    }
    
    /**
     * 
     */
    public function test_collection_method_reinject_into_collection()
    {
        $wrapper = Prime::repository(TestEntity::class)
            ->paginate(1)
                ->groupBy(function($entity) {
                    return $entity->name;
                });
        
        $this->assertInstanceOf(PaginatorInterface::class, $wrapper);
        
        $this->assertEquals('TEST1', $wrapper->get('TEST1')->name);
    }
    
    /**
     * 
     */
    public function test_method_on_collection_doesnt_change_collection_in_paginator()
    {
        $wrapper = Prime::repository(TestEntity::class)
            ->paginate(1);
        
        $collection = $wrapper->collection()->groupBy(function($entity) {
            return $entity->name;
        });
        
        $this->assertEquals(null, $wrapper->get('TEST1'));
        $this->assertEquals('TEST1', $collection->get('TEST1')->name);
    }
    
    /**
     * 
     */
    public function test_same_test_with_map_method()
    {
        $wrapper = Prime::repository(TestEntity::class)
            ->paginate(1);
        
        $collection = $wrapper->collection()->map(function($entity) {
            return $entity->name;
        });
        
        $this->assertEquals('TEST1', $wrapper->get(0)->name);
        $this->assertEquals('TEST1', $collection->get(0));
    }

    /**
     *
     */
    public function test_collection_to_array()
    {
        $wrapper = TestEntity::paginate(1);
        $expected = [
            'items'   => [TestEntity::findOne(['name' => 'TEST1'])->toArray()],
            'page'    => $wrapper->page(),
            'maxRows' => $wrapper->pageMaxRows(),
            'size'    => $wrapper->size(),
        ];

        $this->assertEquals($expected, $wrapper->toArray());
    }

    /**
     *
     */
    public function test_with_distinct()
    {
        $this->pack()
            ->nonPersist([
                $cust1 = new Customer([
                    'id' => 1,
                    'name' => 'DreamWorks'
                ]),
                $cust2 = new Customer([
                    'id' => 2,
                    'name' => 'Disney'
                ]),
                new User([
                    'id' => 1,
                    'name' => 'Shrek',
                    'customer' => $cust1,
                    'roles' => []
                ]),
                new User([
                    'id' => 2,
                    'name' => 'Mickey',
                    'customer' => $cust2,
                    'roles' => []
                ]),
                new User([
                    'id' => 3,
                    'name' => 'Donald',
                    'customer' => $cust2,
                    'roles' => []
                ]),
            ])
        ;

        /** @var Paginator $paginator */
        $paginator = Customer::where('users.name', ['Shrek', 'Mickey', 'Donald'])->distinct()->paginate(1);

        $this->assertEquals(2, $paginator->size());
    }
}
