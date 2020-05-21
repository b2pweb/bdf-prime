<?php

namespace Bdf\Prime\Collection;

use Bdf\Prime\Prime;
use Bdf\Prime\PrimeSerializableEntity;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Serializer\PaginatorNormalizer;
use Bdf\Prime\Serializer\PrimeCollectionNormalizer;
use Bdf\Prime\TestEntity;
use Bdf\Serializer\Normalizer\ObjectNormalizer;
use Bdf\Serializer\SerializerBuilder;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ArrayCollectionTest extends TestCase
{
    use PrimeTestCase;

    protected $collection;
    
    /**
     * 
     */
    protected function setUp(): void
    {
        $this->configurePrime();

        $serializer = SerializerBuilder::create()
            ->build();

        $serializer->getLoader()
            ->addNormalizer(new PrimeCollectionNormalizer($this->prime()))
            ->addNormalizer(new PaginatorNormalizer())
            ->addNormalizer(new ObjectNormalizer())
        ;

        Prime::service()->setSerializer($serializer);

        $this->collection = new ArrayCollection([
            1 => new TestEntity(['id' => 1, 'name' => 'Test1']),
            2 => new TestEntity(['id' => 2, 'name' => 'Test1']),
            3 => new TestEntity(['id' => 3, 'name' => 'Test2']),
        ]);
    }
    
    /**
     * 
     */
    public function test_common_interface()
    {
        $this->assertEquals(3, count($this->collection), 'is countable');
        
        foreach ($this->collection as $id => $entity) {
            $this->assertEquals($id, $entity->id, 'iterator');
        }
    }
    
    public function test_constructor()
    {
        $collection = new ArrayCollection('test');
        $this->assertEquals(['test'], $collection->all());
        
        $collection = new ArrayCollection(['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $collection->all());
        
        $collection = new ArrayCollection(new ArrayCollection(['key' => 'value']));
        $this->assertEquals(['key' => 'value'], $collection->all());
    }
    
    /**
     * 
     */
    public function test_push()
    {
        $collection = new ArrayCollection();
        $collection->push('test');
        
        $this->assertTrue($collection->contains('test'));
    }
    
    /**
     * 
     */
    public function test_push_all()
    {
        $collection = new ArrayCollection();
        $collection->pushAll(['test1', 'test2']);
        
        $this->assertEquals(['test1', 'test2'], $collection->all());
    }
    
    /**
     * 
     */
    public function test_put_get()
    {
        $collection = new ArrayCollection();
        $collection->put('key', 'test');
        
        $this->assertTrue($collection->has('key'));
        $this->assertEquals('test', $collection->get('key'));
    }
    
    /**
     * 
     */
    public function test_array_access()
    {
        $collection = new ArrayCollection();
        $collection['key'] = 'test';
        
        $this->assertTrue(isset($collection['key']));
        $this->assertEquals('test', $collection['key']);
        
        unset($collection['key']);
        $this->assertFalse(isset($collection['key']));
    }
    
    /**
     * 
     */
    public function test_keys()
    {
        $collection = new ArrayCollection();
        $collection->pushAll(['test1' => 'value1', 'test2' => 'value2']);
        
        $this->assertEquals(['test1', 'test2'], $collection->keys());
    }
    
    /**
     * 
     */
    public function test_indexOf()
    {
        $collection = new ArrayCollection([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);
        
        $this->assertEquals('key2', $collection->indexOf('value2'));
    }
    
    /**
     * 
     */
    public function test_indexOf_with_callable()
    {
        $collection = new ArrayCollection([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);
        
        $found = $collection->indexOf(function($value, $key) {
            if ($key === 'key2' && $value === 'value2') {
                return true;
            }
        });
        
        $this->assertEquals('key2', $found);
    }
    
    /**
     * 
     */
    public function test_indexOf_not_found()
    {
        $collection = new ArrayCollection();
        
        $this->assertEquals(false, $collection->indexOf('value2'));
    }
    
    /**
     * 
     */
    public function test_remove()
    {
        $this->collection->remove(1);
        
        $this->assertEquals(2, $this->collection->count());
    }
    
    /**
     * 
     */
    public function test_contains_with_scalar()
    {
        $this->collection = new ArrayCollection([
            1 => 'Test1',
            2 => 'Test2',
            3 => 'Test3',
        ]);
        
        $this->assertTrue($this->collection->contains('Test2'));
        $this->assertFalse($this->collection->contains('Not present'));
    }
    
    /**
     * 
     */
    public function test_contains_with_object()
    {
        $this->assertTrue($this->collection->contains(function($entity) {
            if ($entity->id == 2) {
                return true;
            }
        }));
        
        $this->assertFalse($this->collection->contains(function($entity) {
            if ($entity->id == 'not present') {
                return true;
            }
        }));
    }
    
    /**
     * 
     */
    public function test_map_collection()
    {
        $expected = [
            1 => new TestEntity(['id' => 1, 'name' => 'test']),
            2 => new TestEntity(['id' => 2, 'name' => 'test']),
            3 => new TestEntity(['id' => 3, 'name' => 'test']),
        ];
        
        $new = $this->collection->map(function($entity) {
            $entity->name = 'test';
            return $entity;
        });
        
        $this->assertEquals($expected, $new->all());
    }
    
    /**
     * 
     */
    public function test_basic_filter()
    {
        $expected = [
            'key1' => 'value1',
        ];
        
        $collection = new ArrayCollection([
            'key1' => 'value1',
            'key2' => '',
        ]);
        
        $new = $collection->filter();
        
        $this->assertEquals($expected, $new->all());
    }
    
    /**
     * 
     */
    public function test_filter_collection()
    {
        $expected = [
            2 => new TestEntity(['id' => 2, 'name' => 'Test1']),
            3 => new TestEntity(['id' => 3, 'name' => 'Test2']),
        ];
        
        $new = $this->collection->filter(function($entity) {
            return $entity->id > 1;
        });
        
        $this->assertEquals($expected, $new->all());
    }
    
    /**
     * 
     */
    public function test_simple_group_by()
    {
        $expected = [
            'Test1' => new TestEntity(['id' => 2, 'name' => 'Test1']),
            'Test2' => new TestEntity(['id' => 3, 'name' => 'Test2']),
        ];
        
        $new = $this->collection->groupBy('name');
        
        $this->assertEquals($expected, $new->all());
    }
    
    /**
     * 
     */
    public function test_group_by_with_custom_needs_callable()
    {
        $this->expectException('LogicException');
        
        $this->collection->groupBy('name', ArrayCollection::GROUPBY_CUSTOM);
    }
    
    /**
     * 
     */
    public function test_combine_group_by()
    {
        $expected = [
            'Test1' => [new TestEntity(['id' => 1, 'name' => 'Test1']), new TestEntity(['id' => 2, 'name' => 'Test1'])],
            'Test2' => [new TestEntity(['id' => 3, 'name' => 'Test2'])],
        ];
        
        $new = $this->collection->groupBy('name', ArrayCollection::GROUPBY_COMBINE);
        
        $this->assertEquals($expected, $new->all());
    }
    
    /**
     * 
     */
    public function test_preserve_group_by()
    {
        $expected = [
            'Test1' => [
                1 => new TestEntity(['id' => 1, 'name' => 'Test1']), 
                2 => new TestEntity(['id' => 2, 'name' => 'Test1'])
            ],
            'Test2' => [
                3 => new TestEntity(['id' => 3, 'name' => 'Test2'])
            ],
        ];
        
        $new = $this->collection->groupBy('name', ArrayCollection::GROUPBY_PRESERVE);
        
        $this->assertEquals($expected, $new->all());
    }
    
    /**
     * 
     */
    public function test_group_by_with_callback()
    {
        $expected = [
            101 => new TestEntity(['id' => 1, 'name' => 'Test1']), 
            102 => new TestEntity(['id' => 2, 'name' => 'Test1']),
            103 => new TestEntity(['id' => 3, 'name' => 'Test2']),
        ];
        
        $new = $this->collection->groupBy(function($entity) {
            return 100 + $entity->id;
        });
        
        $this->assertEquals($expected, $new->all());
    }
    
    /**
     * 
     */
    public function test_group_by_custom_mode()
    {
        $expected = [
            101 => new TestEntity(['id' => 1, 'name' => 'Test1']), 
            102 => new TestEntity(['id' => 2, 'name' => 'Test1']),
            103 => new TestEntity(['id' => 3, 'name' => 'Test2']),
        ];
        
        $new = $this->collection->groupBy(function($entity, $key, &$result) {
            $result[100 + $entity->id] = $entity;
        }, ArrayCollection::GROUPBY_CUSTOM);
        
        $this->assertEquals($expected, $new->all());
    }
    
    /**
     * 
     */
    public function test_merge()
    {
        $this->collection = new ArrayCollection([
            'Test1' => new TestEntity(['id' => 1, 'name' => 'Test1']),
            'Test2' => new TestEntity(['id' => 2, 'name' => 'Test2']),
            'Test3' => new TestEntity(['id' => 3, 'name' => 'Test3']),
        ]);
        
        $expected = [
            'Test1' => new TestEntity(['id' => 1, 'name' => 'NEW']),
            'Test2' => new TestEntity(['id' => 2, 'name' => 'Test2']),
            'Test3' => new TestEntity(['id' => 3, 'name' => 'Test3']),
            'Test4' => new TestEntity(['id' => 4, 'name' => 'Test4']),
        ];
        
        $new = $this->collection->merge([
            'Test1' => new TestEntity(['id' => 1, 'name' => 'NEW']),
            'Test4' => new TestEntity(['id' => 4, 'name' => 'Test4']),
        ]);
        
        $this->assertEquals($expected, $new->all());
    }
    
    /**
     * 
     */
    public function test_sort_scalar_value()
    {
        $this->collection = new ArrayCollection([
            3 => 'Test3',
            2 => 'Test2',
            1 => 'Test1',
        ]);
        
        $expected = [
            1 => 'Test1',
            2 => 'Test2',
            3 => 'Test3',
        ];
        
        $new = $this->collection->sort();
        
        $this->assertSame($expected, $new->all());
    }
    
    /**
     * 
     */
    public function test_sort_complex_value()
    {
        $entity1 = new TestEntity(['id' => 1, 'name' => 'Test1']);
        $entity2 = new TestEntity(['id' => 2, 'name' => 'Test2']);
        $entity3 = new TestEntity(['id' => 3, 'name' => 'Test3']);
        
        $this->collection = new ArrayCollection([
            'Test3' => $entity3,
            'Test2' => $entity2,
            'Test1' => $entity1,
        ]);
        
        $expected = [
            'Test1' => $entity1,
            'Test2' => $entity2,
            'Test3' => $entity3,
        ];
        
        $new = $this->collection->sort(function($a, $b) {
            return strcmp($a->name, $b->name);
        });
        
        $this->assertSame($expected, $new->all());
    }

    /**
     *
     */
    public function test_entities_to_array()
    {
        $entity1 = new TestEntity(['id' => 1, 'name' => 'Test1']);
        $entity2 = new TestEntity(['id' => 2, 'name' => 'Test2']);
        $entity3 = new TestEntity(['id' => 3, 'name' => 'Test3']);

        $this->collection = new ArrayCollection([
            'Test3' => $entity3,
            'Test2' => $entity2,
            'Test1' => $entity1,
        ]);

        $array = $this->collection->toArray();

        $this->assertTrue(is_array($array));
        $this->assertSame($entity1->toArray(), $array['Test1']);
        $this->assertSame($entity2->toArray(), $array['Test2']);
        $this->assertSame($entity3->toArray(), $array['Test3']);
    }

    /**
     *
     */
    public function test_array_to_array()
    {
        $entity1 = ['id' => 1, 'name' => 'Test1'];
        $entity2 = ['id' => 2, 'name' => 'Test2'];
        $entity3 = ['id' => 3, 'name' => 'Test3'];

        $this->collection = new ArrayCollection([
            'Test3' => $entity3,
            'Test2' => $entity2,
            'Test1' => $entity1,
        ]);

        $array = $this->collection->toArray();

        $this->assertTrue(is_array($array));
        $this->assertSame($entity1, $array['Test1']);
        $this->assertSame($entity2, $array['Test2']);
        $this->assertSame($entity3, $array['Test3']);
    }

    /**
     *
     */
    public function test_object_to_array()
    {
        $entity1 = (object)['id' => 1, 'name' => 'Test1'];
        $entity2 = (object)['id' => 2, 'name' => 'Test2'];
        $entity3 = (object)['id' => 3, 'name' => 'Test3'];

        $this->collection = new ArrayCollection([
            'Test3' => $entity3,
            'Test2' => $entity2,
            'Test1' => $entity1,
        ]);

        $array = $this->collection->toArray();

        $this->assertTrue(is_array($array));
        $this->assertSame((array)$entity1, $array['Test1']);
        $this->assertSame((array)$entity2, $array['Test2']);
        $this->assertSame((array)$entity3, $array['Test3']);
    }

    /**
     *
     */
    public function test_scalar_to_array()
    {
        $entity1 = 'Test1';
        $entity2 = 'Test2';
        $entity3 = 'Test3';

        $this->collection = new ArrayCollection([
            'Test3' => $entity3,
            'Test2' => $entity2,
            'Test1' => $entity1,
        ]);

        $array = $this->collection->toArray();

        $this->assertTrue(is_array($array));
        $this->assertSame($entity1, $array['Test1']);
        $this->assertSame($entity2, $array['Test2']);
        $this->assertSame($entity3, $array['Test3']);
    }

    /**
     *
     */
    public function test_toJson_group_by()
    {
        $collection = new ArrayCollection([
            new PrimeSerializableEntity('Robert', 'robert.baratheon@b2pweb.com', DateTime::createFromFormat(DateTime::ATOM, '2016-12-21T16:35:40+01:00')),
            new PrimeSerializableEntity('Jaime', 'jaime@lannister.com', DateTime::createFromFormat(DateTime::ATOM, '2016-02-21T16:35:40+01:00')),
        ]);

        $this->assertEquals(
            '{"Robert":{"name":"Robert","email":"robert.baratheon@b2pweb.com","subscriptionDate":"2016-12-21T16:35:40+01:00"},"Jaime":{"name":"Jaime","email":"jaime@lannister.com","subscriptionDate":"2016-02-21T16:35:40+01:00"}}',
            $collection->groupBy('name')->toJson()
        );
    }

    /**
     *
     */
    public function test_toJson_no_group()
    {
        $collection = new ArrayCollection([
            new PrimeSerializableEntity('Robert', 'robert.baratheon@b2pweb.com', DateTime::createFromFormat(DateTime::ATOM, '2016-12-21T16:35:40+01:00')),
            new PrimeSerializableEntity('Jaime', 'jaime@lannister.com', DateTime::createFromFormat(DateTime::ATOM, '2016-02-21T16:35:40+01:00')),
        ]);

        $this->assertEquals(
            '[{"name":"Robert","email":"robert.baratheon@b2pweb.com","subscriptionDate":"2016-12-21T16:35:40+01:00"},{"name":"Jaime","email":"jaime@lannister.com","subscriptionDate":"2016-02-21T16:35:40+01:00"}]',
            $collection->toJson()
        );
    }
}