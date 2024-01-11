<?php

namespace Bdf\Prime\Connection;

use Bdf\Prime\Configuration;
use Bdf\Prime\Connection\Configuration\ConfigurationResolver;
use Bdf\Prime\Exception\DBALException;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ConnectionRegistryTest extends TestCase
{
    /**
     * 
     */
    public function test_get_unknown_connection()
    {
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('Connection name "test" is not set');

        $manager = new ConnectionRegistry();
        $manager->getConnection('test');
    }
    
    /**
     * 
     */
    public function test_get_connection_from_config()
    {
        $registry = new ConnectionRegistry();
        $registry->declareConnection('test', ['adapter' => 'sqlite', 'memory' => true]);

        $connection = $registry->getConnection('test');
        
        $this->assertEquals('sqlite', $connection->getDatabasePlatform()->getName());
    }

    /**
     * @dataProvider dsnProvider
     */
    public function test_dsn($dsn, $expectedParams)
    {
        $registry = new ConnectionRegistry();
        $registry->declareConnection('test', $dsn);

        $this->assertEquals($expectedParams, $registry->getConnection('test')->getParams());
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
        $registry->declareConnection('bar', ['adapter' => 'sqlite', 'memory' => true]);
        $registry->declareConnection('foo', ['adapter' => 'sqlite', 'memory' => true]);

        $this->assertSame(['bar', 'foo'], $registry->getConnectionNames());
    }

    /**
     *
     */
    public function test_configuration_resolver()
    {
        $configuration = new Configuration();
        $configuration->setDisableTypeComments(true);

        $resolver = new ConfigurationResolver();
        $resolver->addConfiguration('foo', $configuration);

        $registry = new ConnectionRegistry([], null, $resolver);
        $registry->declareConnection('foo', ['adapter' => 'sqlite', 'memory' => true]);
        $registry->declareConnection('bar', ['adapter' => 'sqlite', 'memory' => true]);

        $this->assertEquals($configuration->withName('foo'), $registry->getConnection('foo')->getConfiguration());
        $this->assertNotEquals($configuration->withName('bar'), $registry->getConnection('bar')->getConfiguration());
    }
}