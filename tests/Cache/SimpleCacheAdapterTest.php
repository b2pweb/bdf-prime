<?php

namespace Bdf\Prime\Cache;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 *
 */
class SimpleCacheAdapterTest extends TestCase
{
    private SimpleCacheAdapter $cache;

    protected function setUp(): void
    {
        $this->cache = new SimpleCacheAdapter(new Psr16Cache(new ArrayAdapter()));
    }

    /**
     * 
     */
    public function test_get_on_unknow_key()
    {
        $this->assertNull($this->cache->get((new CacheKey())->setNamespace('namespace')->setKey('id')));
    }
    
    /**
     * 
     */
    public function test_set_in_namespace()
    {
        $key = (new CacheKey())->setNamespace('namespace')->setKey('id');
        $this->cache->set($key, 'data');
        
        $this->assertEquals('data', $this->cache->get($key));
    }

    /**
     *
     */
    public function test_set_with_reserved_characters()
    {
        $key = (new CacheKey())->setNamespace('/:namespace:/')->setKey('(id)');
        $this->cache->set($key, 'data');

        $this->assertEquals('data', $this->cache->get($key));
    }

    /**
     *
     */
    public function test_set_unit_without_lifetime()
    {
        $cache = new SimpleCacheAdapter($inner = $this->createMock(\Psr\SimpleCache\CacheInterface::class));
        $inner->expects($this->once())->method('set')->with('namespace%5B1%5D%5Bid%5D', 'data', null);

        $key = (new CacheKey())->setNamespace('namespace')->setKey('id');

        $cache->set($key, 'data');
    }

    /**
     *
     */
    public function test_set_unit_with_lifetime()
    {
        $cache = new SimpleCacheAdapter($inner = $this->createMock(\Psr\SimpleCache\CacheInterface::class));
        $inner->expects($this->once())->method('set')->with('namespace%5B1%5D%5Bid%5D', 'data', 123);

        $key = (new CacheKey())->setNamespace('namespace')->setKey('id')->setLifetime(123);

        $cache->set($key, 'data');
    }
    
    /**
     * 
     */
    public function test_delete_in_namespace()
    {
        $key1 = (new CacheKey())->setNamespace('namespace')->setKey('id1');
        $key2 = (new CacheKey())->setNamespace('namespace')->setKey('id2');
        $key3 = (new CacheKey())->setNamespace('other')->setKey('id');

        $this->cache->set($key1, 'data1');
        $this->cache->set($key2, 'data2');
        $this->cache->set($key3, 'data');
        
        $this->cache->delete($key1);
        
        $this->assertNull($this->cache->get($key1));
        $this->assertEquals('data2', $this->cache->get($key2));
        $this->assertEquals('data', $this->cache->get($key3));
    }
    
    /**
     * 
     */
    public function test_flush_namespace()
    {
        $key1 = (new CacheKey())->setNamespace('namespace')->setKey('id1');
        $key2 = (new CacheKey())->setNamespace('namespace')->setKey('id2');
        $key3 = (new CacheKey())->setNamespace('other')->setKey('id');

        $this->cache->set($key1, 'data1');
        $this->cache->set($key2, 'data2');
        $this->cache->set($key3, 'data');

        $this->cache->flush('namespace');
        
        $this->assertNull($this->cache->get($key2));
        $this->assertNull($this->cache->get($key2));
        $this->assertEquals('data', $this->cache->get($key3));
    }
    
    /**
     * 
     */
    public function test_clear()
    {
        $key1 = (new CacheKey())->setNamespace('namespace')->setKey('id1');
        $key2 = (new CacheKey())->setNamespace('namespace')->setKey('id2');
        $key3 = (new CacheKey())->setNamespace('other')->setKey('id');

        $this->cache->set($key1, 'data1');
        $this->cache->set($key2, 'data2');
        $this->cache->set($key3, 'data');

        $this->cache->clear();
        
        $this->assertNull($this->cache->get($key1));
        $this->assertNull($this->cache->get($key2));
        $this->assertNull($this->cache->get($key3));
    }
}