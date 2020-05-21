<?php

namespace Bdf\Prime;

use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Exception\DBALException;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ConnectionManagerTest extends TestCase
{
    /**
     * 
     */
    public function test_simple_constructor()
    {
        $manager = new ConnectionManager();
        
        $this->assertEquals(new Configuration(), $manager->config());
    }
    
    /**
     * 
     */
    public function test_constructor_with_array_config()
    {
        $manager = new ConnectionManager(['resultCache' => 'cache']);
        
        $this->assertEquals('cache', $manager->config()->getResultCache());
    }
    
    /**
     * 
     */
    public function test_constructor_with_config()
    {
        $manager = new ConnectionManager(new Configuration(['resultCache' => 'cache']));
        
        $this->assertEquals('cache', $manager->config()->getResultCache());
    }
    
    /**
     * 
     */
    public function test_set_get_default_connection()
    {
        $manager = new ConnectionManager();
        $manager->setDefaultConnection('new default');
        
        $this->assertEquals('new default', $manager->getDefaultConnection());
    }
    
    /**
     * 
     */
    public function test_add_remove_connection()
    {
        $manager = new ConnectionManager();
        $manager->addConnection('test', 'sqlite::memory:');
        
        $this->assertEquals(['test'], $manager->connectionNames());
        
        $manager->removeConnection('test');
        
        $this->assertEquals([], $manager->connectionNames());
    }
    
    /**
     * 
     */
    public function test_remove_unknown_connection()
    {
        $manager = new ConnectionManager();
        
        $manager->addConnection('test', 'sqlite::memory:');
        $manager->removeConnection('unknown');
        
        $this->assertEquals(['test'], $manager->connectionNames());
    }
    
    /**
     * 
     */
    public function test_add_connection_set_default_connection()
    {
        $manager = new ConnectionManager();
        
        $this->assertEquals(null, $manager->getDefaultConnection());
        $manager->addConnection('test', 'sqlite::memory:');
        $this->assertEquals('test', $manager->getDefaultConnection());
    }
    
    /**
     * 
     */
    public function test_add_connection_with_existing_name()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Connection for "test" already exists. Connection name must be unique.');

        $manager = new ConnectionManager();
        $manager->addConnection('test', 'sqlite::memory:');
        $manager->addConnection('test', 'sqlite::memory:');
    }
    
    /**
     * 
     */
    public function test_get_connections()
    {
        $manager = new ConnectionManager();
        $manager->addConnection('test', 'sqlite::memory:');
        
        $connections = $manager->connections();
        
        $this->assertTrue(isset($connections['test']));
        $this->assertInstanceOf(SimpleConnection::class, $connections['test']);
    }
    
    /**
     * 
     */
    public function test_basic_connection()
    {
        $connection = $this->getMockBuilder(SimpleConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $manager = new ConnectionManager();
        $manager->addConnection('test', $connection);
        
        $this->assertSame($connection, $manager->connection());
    }
    
    /**
     * 
     */
    public function test_get_unknown_connection()
    {
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('Connection name "test" is not set');

        $manager = new ConnectionManager();
        $manager->connection('test');
    }
    
    /**
     * 
     */
    public function test_get_connection_from_config()
    {
        $manager = new ConnectionManager();
        $manager->config()->setDbConfig(['test' => ['adapter' => 'sqlite', 'memory' => true]]);
        
        $connection = $manager->connection('test');
        
        $this->assertEquals('sqlite', $connection->getDatabasePlatform()->getName());
    }

    /**
     *
     */
    public function test_register_driver_map()
    {
        $manager = new ConnectionManager();

        $manager->registerDriverMap('test-map', 'driver', 'wrapper');
        $this->assertEquals(['driver', 'wrapper'], $manager->getDriverMap('test-map'));

        $manager->unregisterDriverMap('test-map');
        $this->assertNull($manager->getDriverMap('test-map'));
    }

    /**
     * @dataProvider dsnProvider
     */
    public function test_dsn($dsn, $expectedParams)
    {
        $manager = new ConnectionManager();
        $connection = $manager->addConnection('test', $dsn);

        $this->assertEquals($expectedParams, $connection->getParams());
    }
    public function dsnProvider()
    {
        yield ['sqlite::memory:', [
            'driver' => 'pdo_sqlite',
            'host' => null,
            'port' => null,
            'user' => null,
            'password' => null,
            'memory' => true,
            'charset' => 'utf8',
            'wrapperClass' => SimpleConnection::class,
        ]];

        yield ['sqlite:/tmp/test.db', [
            'driver' => 'pdo_sqlite',
            'host' => null,
            'port' => null,
            'user' => null,
            'password' => null,
            'path' => '/tmp/test.db',
            'charset' => 'utf8',
            'wrapperClass' => SimpleConnection::class,
        ]];

        yield ['pdo+mysql://localhost/foo/?charset=latin1', [
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'port' => null,
            'user' => null,
            'password' => null,
            'dbname' => 'foo',
            'charset' => 'latin1',
            'wrapperClass' => SimpleConnection::class,
        ]];

        yield ['pdo+mysql:host=localhost;dbname=foo;charset=latin1', [
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'port' => null,
            'user' => null,
            'password' => null,
            'dbname' => 'foo',
            'charset' => 'latin1',
            'wrapperClass' => SimpleConnection::class,
        ]];

        yield [
            [
                'url' => 'pdo+mysql:host=localhost;dbname=foo',
                'charset' => 'latin1',
            ],
            [
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'port' => null,
                'user' => null,
                'password' => null,
                'dbname' => 'foo',
                'charset' => 'latin1',
                'wrapperClass' => SimpleConnection::class,
            ]
        ];
    }

    /**
     *
     */
    public function test_all_connection_names()
    {
        $manager = new ConnectionManager();
        $manager->config()->setDbConfig(['foo' => ['adapter' => 'sqlite', 'memory' => true]]);
        $manager->addConnection('bar', 'sqlite::memory:');

        $this->assertSame(['bar'], $manager->connectionNames());
        $this->assertSame(['bar', 'foo'], $manager->allConnectionNames());
    }
}