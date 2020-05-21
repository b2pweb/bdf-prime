<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Entity\Hydrator\HydratorGeneratedInterface;
use Bdf\Prime\Entity\Hydrator\MapperHydrator;
use Bdf\Prime\Mapper\NameResolver\CallbackResolver;
use Bdf\Prime\Mapper\NameResolver\ResolverInterface;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestEntity;
use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MapperFactoryTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    public function setUp(): void
    {
        $this->primeStart();
    }

    /**
     *
     */
    public function tearDown(): void
    {
        $this->unsetPrime();
    }

    /**
     * 
     */
    public function test_default()
    {
        $factory = new MapperFactory();
        
        $this->assertInstanceOf(ResolverInterface::class, $factory->getNameResolver());
    }
    
    /**
     * 
     */
    public function test_setter_getter_cache()
    {
        $factory = new MapperFactory();
        
        $factory->setCache($cache = new ArrayCachePool());
        $this->assertEquals($cache, $factory->getCache());
    }
    
    /**
     * 
     */
    public function test_is_mapper()
    {
        $factory = new MapperFactory();
        
        $this->assertTrue($factory->isMapper('Bdf\Prime\TestEntityMapper'));
        $this->assertFalse($factory->isMapper('Bdf\Prime\TestEntity'));
    }
    
    /**
     * 
     */
    public function test_is_entity_with_default_resolver()
    {
        $factory = new MapperFactory();
        
        $this->assertFalse($factory->isEntity('Bdf\Prime\TestEntityMapper'));
        $this->assertTrue($factory->isEntity('Bdf\Prime\TestEntity'));
    }
    
    /**
     * 
     */
    public function test_createMapper()
    {
        $factory = new MapperFactory();
        $mapper = $factory->createMapper(Prime::service(), 'Bdf\Prime\TestEntityMapper');
        
        $this->assertInstanceOf('Bdf\Prime\TestEntityMapper', $mapper);
        $this->assertEquals('Bdf\Prime\TestEntity', $mapper->getEntityClass());
        $this->assertInstanceOf(MapperHydrator::class, $mapper->hydrator());
    }

    /**
     *
     */
    public function test_createMapper_custom_hydrator()
    {
        $factory = new MapperFactory();
        $service = Prime::service();

        $hydrator = $this->createMock(HydratorGeneratedInterface::class);
        $service->hydrators()->add(TestEntity::class, $hydrator);

        $mapper = $factory->createMapper($service, 'Bdf\Prime\TestEntityMapper');

        $this->assertSame($hydrator, $mapper->hydrator());
    }
    
    /**
     * 
     */
    public function test_createMapper_assert_class_is_mapper()
    {
        $factory = new MapperFactory();
        
        $this->assertEquals(null, $factory->createMapper(Prime::service(), 'Bdf\Prime\TestEntity'));
    }
    
    /**
     * 
     */
    public function test_createMapper_reverse_entity_name()
    {
        $factory = new MapperFactory(new CallbackResolver(
            function($className) {},
            function($className) {
                return str_replace('EntityMapper', '', $className);
            }
        ));
        
        $mapper = $factory->createMapper(Prime::service(), 'Bdf\Prime\TestEntityMapper');
        
        $this->assertEquals('Bdf\Prime\Test', $mapper->getEntityClass());
    }
    
    /**
     * 
     */
    public function test_createMapper_save_metadata_in_cache()
    {
        $factory = new MapperFactory();
        $factory->setCache($cache = new ArrayCachePool());
        
        $mapper = $factory->createMapper(Prime::service(), 'Bdf\Prime\TestEntityMapper');
        
        $this->assertTrue($cache->has('Bdf.Prime.TestEntityMapper'));
        $this->assertEquals($mapper->metadata(), $cache->get('Bdf.Prime.TestEntityMapper'));
    }
    
    /**
     * 
     */
    public function test_createMapper_load_metadata_from_cache()
    {
        $cache = $this->createPartialMock(ArrayCachePool::class, ['get', 'set']);
        $cache->expects($this->once())->method('get')->will($this->returnValue(new Metadata()));
        $cache->expects($this->never())->method('set');
        
        $factory = new MapperFactory();
        $factory->setCache($cache);
        
        $factory->createMapper(Prime::service(), 'Bdf\Prime\TestEntityMapper');
    }
    
    /**
     * 
     */
    public function test_basic_build()
    {
        $factory = new MapperFactory();
        
        $this->assertInstanceOf('Bdf\Prime\TestEntityMapper', $factory->build(Prime::service(), 'Bdf\Prime\TestEntity'));
    }
}
