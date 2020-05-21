<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\Hydrator\HydratorInterface;
use Bdf\Prime\Entity\Hydrator\HydratorRegistry;
use Bdf\Prime\Entity\InitializableInterface;
use Bdf\Prime\Entity\Instantiator\Instantiator;
use Bdf\Prime\Entity\Instantiator\RegistryInstantiator;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Serializer\SerializerInterface;
use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 *
 */
class ServiceLocatorTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $service = new ServiceLocator();

        $this->assertInstanceOf(ConnectionManager::class, $service->connections());
        $this->assertInstanceOf(Configuration::class, $service->config());
        $this->assertInstanceOf(HydratorRegistry::class, $service->hydrators());
        $this->assertInstanceOf(RegistryInstantiator::class, $service->instantiator());
    }

    /**
     *
     */
    public function test_service_container()
    {
        $di = $this->createMock(ContainerInterface::class);
        $service = new ServiceLocator();
        $service->setDI($di);

        $this->assertSame($di, $service->di());
    }

    /**
     * 
     */
    public function test_set_get_connectionManager()
    {
        $connectionManager = new ConnectionManager();
        
        $service = new ServiceLocator($connectionManager);
        
        $this->assertSame($connectionManager, $service->connections());
    }
    
    /**
     * 
     */
    public function test_set_get_mapperFactory()
    {
        $mapperFactory = new \Bdf\Prime\Mapper\MapperFactory();
        
        $service = new ServiceLocator(null, $mapperFactory);
        
        $this->assertSame($mapperFactory, $service->mappers());
    }

    /**
     *
     */
    public function test_set_get_instantiator()
    {
        $instantiator = new Instantiator();

        $service = new ServiceLocator(null, null, $instantiator);

        $this->assertSame($instantiator, $service->instantiator());
    }

    /**
     * 
     */
    public function test_config()
    {
        $service = new ServiceLocator();
        
        $this->assertInstanceOf(Configuration::class, $service->config());
    }
    
    /**
     * 
     */
    public function test_meta_cache()
    {
        $cache = new ArrayCachePool();
        
        $service = new ServiceLocator(new ConnectionManager([
            'metadataCache' => $cache,
        ]));
        
        $this->assertEquals($cache, $service->mappers()->getCache());
    }
    
    /**
     * 
     */
    public function test_set_repository()
    {
        $service = new ServiceLocator();
        $repository = $this->createMock(RepositoryInterface::class);
        
        $service->registerRepository('entityClass', $repository);
        
        $this->assertEquals($repository, $service->repository('entityClass'));
    }
    
    /**
     * 
     */
    public function test_get_repository_names()
    {
        $service = new ServiceLocator();
        $service->registerRepository('entityClass', $this->createMock(RepositoryInterface::class));
        
        $this->assertEquals(['entityClass'], $service->repositoryNames());
    }
    
    /**
     * 
     */
    public function test_unset_repository()
    {
        $service = new ServiceLocator();
        
        $service->registerRepository('entityClass', $this->createMock(RepositoryInterface::class));
        $service->unregisterRepository('entityClass');
        
        $this->assertEquals([], $service->repositoryNames());
    }

    /**
     *
     */
    public function test_clear_repositories()
    {
        $service = Prime::service();

        $repository = Prime::repository(TestEntity::class);
        $repository->listen('test', function(){});

        $this->assertTrue($repository->hasListeners('test'));
        $this->assertTrue($repository->hasListeners('afterLoad'));

        $service->clearRepositories();

        $this->assertEquals([], $service->repositoryNames());
    }

    /**
     *
     */
    public function test_set_get_serializer()
    {
        $serializer = $this->createMock(SerializerInterface::class);

        $service = new ServiceLocator();
        $service->setSerializer($serializer);

        $this->assertSame($serializer, $service->serializer());
    }

    /**
     *
     */
    public function test_set_get_serializer_resolver()
    {
        $serializer = $this->createMock(SerializerInterface::class);

        $service = new ServiceLocator();
        $service->setSerializer(function() use($serializer) { return $serializer; });

        $this->assertSame($serializer, $service->serializer());
    }

    /**
     *
     */
    public function test_hydrator()
    {
        $hydrator = $this->createMock(HydratorInterface::class);

        $service = new ServiceLocator();
        $service->hydrators()->add("TestEntity", $hydrator);

        $this->assertSame($hydrator, $service->hydrator("TestEntity"));
    }
}


class IntializedEntity implements InitializableInterface
{
    private $name;

    /**
     *
     */
    public function initialize()
    {
        $this->name = 'initialized';
    }

    /**
     *
     */
    public function name()
    {
        return $this->name;
    }
}
