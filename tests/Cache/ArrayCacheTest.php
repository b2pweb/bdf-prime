<?php

namespace Bdf\Prime\Cache;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ArrayCacheTest extends TestCase
{
    /**
     * 
     */
    public function test_get_on_unknow_key()
    {
        $cache = new ArrayCache();
        
        $this->assertNull($cache->get('namespace', 'id'));
    }
    
    /**
     * 
     */
    public function test_set_in_namespace()
    {
        $cache = new ArrayCache();
        
        $cache->set('namespace', 'id', 'data');
        
        $this->assertEquals('data', $cache->get('namespace', 'id'));
    }
    
    /**
     * 
     */
    public function test_delete_in_namespace()
    {
        $cache = new ArrayCache();
        
        $cache->set('namespace', 'id1', 'data1');
        $cache->set('namespace', 'id2', 'data2');
        $cache->set('other', 'id', 'data');
        
        $cache->delete('namespace', 'id1');
        
        $this->assertNull($cache->get('namespace', 'id1'));
        $this->assertEquals('data2', $cache->get('namespace', 'id2'));
        $this->assertEquals('data', $cache->get('other', 'id'));
    }
    
    /**
     * 
     */
    public function test_flush_namespace()
    {
        $cache = new ArrayCache();
        
        $cache->set('namespace', 'id1', 'data1');
        $cache->set('namespace', 'id2', 'data2');
        $cache->set('other', 'id', 'data');
        
        $cache->flush('namespace');
        
        $this->assertNull($cache->get('namespace', 'id1'));
        $this->assertNull($cache->get('namespace', 'id2'));
        $this->assertEquals('data', $cache->get('other', 'id'));
    }
    
    /**
     * 
     */
    public function test_clear()
    {
        $cache = new ArrayCache();
        
        $cache->set('namespace', 'id1', 'data1');
        $cache->set('namespace', 'id2', 'data2');
        $cache->set('other', 'id', 'data');
        
        $cache->clear();
        
        $this->assertNull($cache->get('namespace', 'id1'));
        $this->assertNull($cache->get('namespace', 'id2'));
        $this->assertNull($cache->get('other', 'id'));
    }
}