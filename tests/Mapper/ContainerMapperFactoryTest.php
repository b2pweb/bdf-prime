<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Entity\Hydrator\HydratorGeneratedInterface;
use Bdf\Prime\Entity\Hydrator\MapperHydrator;
use Bdf\Prime\Mapper\NameResolver\ResolverInterface;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestEntity;
use Bdf\Prime\TestEntityMapper;
use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;
use PrimeTests\ArrayContainer;

/**
 *
 */
class ContainerMapperFactoryTest extends TestCase
{
    use PrimeTestCase;

    private ContainerMapperFactory $factory;
    private ArrayContainer $container;

    /**
     *
     */
    public function setUp(): void
    {
        $this->primeStart();

        $this->factory = new ContainerMapperFactory($this->container = new ArrayContainer());
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
        $this->assertInstanceOf(ResolverInterface::class, $this->factory->getNameResolver());
    }
    
    /**
     * 
     */
    public function test_setter_getter_cache()
    {
        $this->factory->setMetadataCache($cache = new ArrayCachePool());
        $this->assertEquals($cache, $this->factory->getMetadataCache());
    }
    
    /**
     * 
     */
    public function test_is_mapper()
    {
        $this->assertFalse($this->factory->isMapper(TestEntityMapper::class));

        $this->container->set(TestEntityMapper::class, new TestEntityMapper($this->prime()));
        $this->assertTrue($this->factory->isMapper(TestEntityMapper::class));

        $this->assertFalse($this->factory->isMapper(TestEntity::class));
    }
    
    /**
     * 
     */
    public function test_is_entity_with_default_resolver()
    {
        $this->assertFalse($this->factory->isEntity(TestEntityMapper::class));
        $this->assertFalse($this->factory->isEntity(TestEntity::class));

        $this->container->set(TestEntityMapper::class, new TestEntityMapper($this->prime()));
        $this->assertTrue($this->factory->isEntity(TestEntity::class));
    }
    
    /**
     * 
     */
    public function test_createMapper_not_in_container()
    {
        $this->assertNull($this->factory->createMapper(Prime::service(), TestEntityMapper::class));
    }

    /**
     *
     */
    public function test_createMapper()
    {
        $this->container->set(TestEntityMapper::class, $inContainer = new TestEntityMapper($this->prime()));
        $mapper = $this->factory->createMapper(Prime::service(), TestEntityMapper::class);

        $this->assertInstanceOf(TestEntityMapper::class, $mapper);
        $this->assertEquals(TestEntity::class, $mapper->getEntityClass());
        $this->assertInstanceOf(MapperHydrator::class, $mapper->hydrator());
        $this->assertSame($inContainer, $mapper);
    }

    /**
     *
     */
    public function test_createMapper_custom_hydrator()
    {
        $service = Prime::service();
        $this->container->set(TestEntityMapper::class, new TestEntityMapper($this->prime()));

        $hydrator = $this->createMock(HydratorGeneratedInterface::class);
        $service->hydrators()->add(TestEntity::class, $hydrator);

        $mapper = $this->factory->createMapper($service, TestEntityMapper::class);

        $this->assertSame($hydrator, $mapper->hydrator());
    }
    
    /**
     * 
     */
    public function test_createMapper_assert_class_is_mapper()
    {
        $this->assertEquals(null, $this->factory->createMapper(Prime::service(), TestEntity::class));
    }

    /**
     * 
     */
    public function test_createMapper_save_metadata_in_cache()
    {
        $this->container->set(TestEntityMapper::class, new TestEntityMapper($this->prime()));
        $this->factory->setMetadataCache($cache = new ArrayCachePool());
        
        $mapper = $this->factory->createMapper(Prime::service(), TestEntityMapper::class);
        
        $this->assertTrue($cache->has('Bdf.Prime.TestEntityMapper'));
        $this->assertEquals($mapper->metadata(), $cache->get('Bdf.Prime.TestEntityMapper'));
    }
    
    /**
     * 
     */
    public function test_createMapper_load_metadata_from_cache()
    {
        $this->container->set(TestEntityMapper::class, new TestEntityMapper($this->prime()));
        $cache = $this->createPartialMock(ArrayCachePool::class, ['get', 'set']);
        $cache->expects($this->once())->method('get')->will($this->returnValue(new Metadata()));
        $cache->expects($this->never())->method('set');
        
        $this->factory->setMetadataCache($cache);
        
        $this->factory->createMapper(Prime::service(), TestEntityMapper::class);
    }
    
    /**
     * 
     */
    public function test_basic_build()
    {
        $this->container->set(TestEntityMapper::class, new TestEntityMapper($this->prime()));
        $this->assertInstanceOf(TestEntityMapper::class, $this->factory->build(Prime::service(), TestEntity::class));
    }
}
