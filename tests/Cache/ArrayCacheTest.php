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
        
        $this->assertNull($cache->get((new CacheKey())->setNamespace('namespace')->setKey('id')));
    }
    
    /**
     * 
     */
    public function test_set_in_namespace()
    {
        $cache = new ArrayCache();

        $key = (new CacheKey())->setNamespace('namespace')->setKey('id');
        $cache->set($key, 'data');
        
        $this->assertEquals('data', $cache->get($key));
    }
    
    /**
     * 
     */
    public function test_delete_in_namespace()
    {
        $cache = new ArrayCache();

        $key1 = (new CacheKey())->setNamespace('namespace')->setKey('id1');
        $key2 = (new CacheKey())->setNamespace('namespace')->setKey('id2');
        $key3 = (new CacheKey())->setNamespace('other')->setKey('id');

        $cache->set($key1, 'data1');
        $cache->set($key2, 'data2');
        $cache->set($key3, 'data');
        
        $cache->delete($key1);
        
        $this->assertNull($cache->get($key1));
        $this->assertEquals('data2', $cache->get($key2));
        $this->assertEquals('data', $cache->get($key3));
    }
    
    /**
     * 
     */
    public function test_flush_namespace()
    {
        $cache = new ArrayCache();

        $key1 = (new CacheKey())->setNamespace('namespace')->setKey('id1');
        $key2 = (new CacheKey())->setNamespace('namespace')->setKey('id2');
        $key3 = (new CacheKey())->setNamespace('other')->setKey('id');

        $cache->set($key1, 'data1');
        $cache->set($key2, 'data2');
        $cache->set($key3, 'data');

        $cache->flush('namespace');
        
        $this->assertNull($cache->get($key2));
        $this->assertNull($cache->get($key2));
        $this->assertEquals('data', $cache->get($key3));
    }
    
    /**
     * 
     */
    public function test_clear()
    {
        $cache = new ArrayCache();

        $key1 = (new CacheKey())->setNamespace('namespace')->setKey('id1');
        $key2 = (new CacheKey())->setNamespace('namespace')->setKey('id2');
        $key3 = (new CacheKey())->setNamespace('other')->setKey('id');

        $cache->set($key1, 'data1');
        $cache->set($key2, 'data2');
        $cache->set($key3, 'data');

        $cache->clear();
        
        $this->assertNull($cache->get($key1));
        $this->assertNull($cache->get($key2));
        $this->assertNull($cache->get($key3));
    }
}