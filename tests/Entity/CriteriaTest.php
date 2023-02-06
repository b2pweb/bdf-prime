<?php

namespace Bdf\Prime\Entity;

use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class CriteriaTest extends TestCase
{
    use PrimeTestCase;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->configurePrime();
    }

    /**
     * 
     */
    public function test_criteria_from_repository()
    {
        $this->assertInstanceOf(Criteria::class, Prime::repository(TestEntity::class)->criteria());
    }
    
    /**
     * 
     */
    public function test_basics()
    {
        $criteria = new Criteria([
            'id' => 2,
            'name :like' => 'test',
        ]);
        
        $this->assertTrue($criteria->exists('id'));
        $this->assertTrue($criteria->exists('name'));
        $this->assertTrue($criteria->exists('name :like'));
        
        $this->assertEquals(2, $criteria->get('id'));
        $this->assertEquals('test', $criteria->get('name'));
        $this->assertEquals('test', $criteria->get('name :like'));
    }
    
    /**
     * 
     */
    public function test_get_default()
    {
        $criteria = new Criteria();
        
        $this->assertEquals(null, $criteria->get('id'));
        $this->assertEquals(3, $criteria->get('id', 3));
    }
    
    /**
     * 
     */
    public function test_add_remove()
    {
        $criteria = new Criteria();
        
        $criteria->add('name :like', 'test');
        $this->assertEquals('test', $criteria->get('name'));
        
        $criteria->remove('name :like');
        $this->assertEquals(null, $criteria->get('name'));
    }
    
    /**
     * 
     */
    public function test_add_and_replace()
    {
        $criteria = new Criteria();
        
        $criteria->add('name :like', 'test');
        $criteria->add('name', 'test%', true);
        
        $this->assertEquals(1, count($criteria->criteria()));
        $this->assertEquals('test%', $criteria->get('name'));
    }
    
    /**
     * 
     */
    public function test_get_criteria()
    {
        $criteria = new Criteria();
        
        $criteria->add('name', 'test%');
        $criteria->add(':limit', 1);
        
        $this->assertEquals(['name' => 'test%'], $criteria->criteria());
    }
    
    /**
     * 
     */
    public function test_get_special()
    {
        $criteria = new Criteria();
        
        $criteria->add(':limit', 1);
        
        $this->assertEquals(1, $criteria->special(':limit'));
        
        $criteria->remove(':limit');
        $this->assertEquals(null, $criteria->get(':limit'));
    }
    
    /**
     * 
     */
    public function test_get_specials()
    {
        $criteria = new Criteria();
        
        $criteria->add('name', 'test%');
        $criteria->add(':limit', 1);
        
        $this->assertEquals([':limit' => 1], $criteria->specials());
    }
    
    /**
     * 
     */
    public function test_get_all()
    {
        $criteria = new Criteria();
        
        $criteria->add('name', 'test%');
        $criteria->add(':limit', 1);
        
        $this->assertEquals(['name' => 'test%', ':limit' => 1], $criteria->all());
    }

    /**
     *
     */
    public function test_iterator()
    {
        $criteria = new Criteria();

        $criteria->add('name', 'test%');
        $criteria->add(':limit', 1);

        $this->assertEquals(['name' => 'test%', ':limit' => 1], iterator_to_array($criteria));
    }

    /**
     * 
     */
    public function test_order()
    {
        $criteria = new Criteria();
        
        $this->assertEquals(null, $criteria->orderType('date'));
        $criteria->order('date', 'desc');
        $this->assertEquals('desc', $criteria->orderType('date'));
    }
    
    /**
     * 
     */
    public function test_page()
    {
        $criteria = new Criteria();
        
        $this->assertEquals(0, $criteria->page());
        $criteria->page(3);
        $this->assertEquals(3, $criteria->page());
    }
    
    /**
     * 
     */
    public function test_page_max_rows()
    {
        $criteria = new Criteria();
        
        $this->assertEquals(0, $criteria->pageMaxRows());
        $criteria->pageMaxRows(30);
        $this->assertEquals(30, $criteria->pageMaxRows());
    }
    
    /**
     * 
     */
    public function test_max_result()
    {
        $criteria = new Criteria();
        
        $this->assertEquals(0, $criteria->maxResults());
        $criteria->maxResults(300);
        $this->assertEquals(300, $criteria->maxResults());
    }
    
    /**
     * 
     */
    public function test_first_result()
    {
        $criteria = new Criteria();
        
        $this->assertEquals(0, $criteria->firstResult());
        $criteria->firstResult(300);
        $this->assertEquals(300, $criteria->firstResult());
    }
    
    /**
     * 
     */
    public function test_array_access()
    {
        $criteria = new Criteria();
        
        $criteria['name'] = 'test%';
        $this->assertTrue(isset($criteria['name']));
        $this->assertEquals('test%', $criteria['name']);
        
        unset($criteria['name']);
        $this->assertFalse(isset($criteria['name']));
    }
}
