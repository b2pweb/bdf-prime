<?php

namespace Bdf\Prime\Collection;

use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class RepositoryCollectionTest extends TestCase
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
            'Bdf\Prime\TestEntity',
        ]);

        $pack->persist([
            new TestEntity(['name' => 'TEST',]),
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
    public function test_array_collection()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $collection = $repository
            ->wrapAs('array')
            ->all();
        
        $this->assertInstanceOf('Bdf\Prime\Collection\ArrayCollection', $collection);
    }
    
    /**
     * 
     */
    public function test_group_by_return_array()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $collection = $repository
            ->by('name')
            ->all();
        
        $this->assertTrue(is_array($collection));
    }
    
    /**
     * 
     */
    public function test_group_by_collection()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $collection = $repository
            ->by('name')
            ->all();
        
        $this->assertTrue(isset($collection['TEST']));
    }
    
    /**
     * 
     */
    public function test_combine_group_by_collection()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $collection = $repository
            ->by('name', true)
            ->all();
        
        $this->assertTrue(is_array($collection['TEST']));
    }
    
    /**
     * 
     */
    public function test_by_and_wrapper()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $collection = $repository
            ->by('name', true)
            ->wrapAs('array')
            ->all();
        
        $this->assertInstanceOf('Bdf\Prime\Collection\ArrayCollection', $collection);
        $this->assertTrue(is_array($collection->get('TEST')));
    }

    /**
     *
     */
    public function test_collection_wrapper()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');

        $collection = $repository
            ->wrapAs('collection')
            ->all();

        $this->assertInstanceOf(EntityCollection::class, $collection);
        $this->assertSame($repository, $collection->repository());
        $this->assertEquals(1, $collection->count());
    }
}
