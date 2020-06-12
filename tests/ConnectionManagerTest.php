<?php

namespace Bdf\Prime;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\ConnectionRegistry;
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
    public function test_constructor_with_config()
    {
        $registry = new ConnectionRegistry([], null, new Configuration(['resultCache' => 'cache']));
        $manager = new ConnectionManager($registry);
        
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
        $connection = $this->createMock(SimpleConnection::class);
        $connection->expects($this->any())->method('getName')->willReturn('test');

        $manager = new ConnectionManager();
        $manager->addConnection($connection);
        
        $this->assertEquals(['test'], $manager->getConnectionNames());
        
        $manager->removeConnection('test');
        
        $this->assertEquals([], $manager->getConnectionNames());
    }
    
    /**
     * 
     */
    public function test_remove_unknown_connection()
    {
        $registry = new ConnectionRegistry();
        $registry->declareConnection('test', 'sqlite::memory:');

        $manager = new ConnectionManager($registry);
        $manager->removeConnection('unknown');
        
        $this->assertEquals(['test'], $manager->getConnectionNames());
    }
    
    /**
     * 
     */
    public function test_add_connection_set_default_connection()
    {
        $manager = new ConnectionManager();

        $connection = $this->createMock(SimpleConnection::class);
        $connection->expects($this->any())->method('getName')->willReturn('test');

        $this->assertEquals(null, $manager->getDefaultConnection());
        $manager->addConnection($connection);
        $this->assertEquals('test', $manager->getDefaultConnection());
    }
    
    /**
     * 
     */
    public function test_add_connection_with_existing_name()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Connection for "test" already exists. Connection name must be unique.');

        $connection = $this->createMock(SimpleConnection::class);
        $connection->expects($this->any())->method('getName')->willReturn('test');

        $manager = new ConnectionManager();
        $manager->addConnection($connection);
        $manager->addConnection($connection);
    }
    
    /**
     * 
     */
    public function test_get_connections()
    {
        $connection = $this->createMock(SimpleConnection::class);
        $connection->expects($this->any())->method('getName')->willReturn('test');

        $manager = new ConnectionManager();
        $manager->addConnection($connection);
        
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
        $connection->expects($this->any())->method('getName')->willReturn('test');
        
        $manager = new ConnectionManager();
        $manager->addConnection($connection);
        
        $this->assertSame($connection, $manager->getConnection());
    }
    
    /**
     * 
     */
    public function test_get_unknown_connection()
    {
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('Connection name "test" is not set');

        $manager = new ConnectionManager();
        $manager->getConnection('test');
    }
    
    /**
     * 
     */
    public function test_get_connection_from_config()
    {
        $registry = new ConnectionRegistry();
        $registry->declareConnection('test', ['adapter' => 'sqlite', 'memory' => true]);
        $manager = new ConnectionManager($registry);

        $connection = $manager->getConnection('test');
        
        $this->assertEquals('sqlite', $connection->getDatabasePlatform()->getName());
    }

    /**
     * @dataProvider dsnProvider
     */
    public function test_dsn($dsn, $expectedParams)
    {
        $registry = new ConnectionRegistry();
        $registry->declareConnection('test', $dsn);
        $manager = new ConnectionManager($registry);

        $this->assertEquals($expectedParams, $manager->getConnection('test')->getParams());
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
    public function test_connection_names()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->any())->method('getName')->willReturn('bar');

        $registry = new ConnectionRegistry();
        $registry->declareConnection('foo', ['adapter' => 'sqlite', 'memory' => true]);
        $registry->declareConnection('bar', ['adapter' => 'sqlite', 'memory' => true]);

        $manager = new ConnectionManager($registry);
        $manager->addConnection($connection);

        $this->assertSame(['bar'], $manager->getCurrentConnectionNames());
        $this->assertSame(['bar', 'foo'], $manager->getConnectionNames());
        $this->assertSame(['bar', 'foo'], $manager->connectionNames());
    }
}