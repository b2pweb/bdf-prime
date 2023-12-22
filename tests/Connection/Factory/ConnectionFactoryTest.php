<?php

namespace Bdf\Prime\Connection\Factory;

use Bdf\Prime\Configuration;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ConnectionFactoryTest extends TestCase
{
    /**
     *
     */
    public function test_register_driver_map()
    {
        $factory = new ConnectionFactory();

        $factory->registerDriverMap('test-map', 'driver', 'wrapper');
        $this->assertEquals(['driver', 'wrapper'], $factory->getDriverMap('test-map'));

        $factory->unregisterDriverMap('test-map');
        $this->assertNull($factory->getDriverMap('test-map'));
    }

    public function test_create_should_set_name_on_configuration()
    {
        $factory = new ConnectionFactory();
        $connection = $factory->create('foo', ['driver' => 'pdo_sqlite', 'memory' => true]);

        $this->assertEquals('foo', $connection->getConfiguration()->getName());
        $this->assertEquals('foo', $connection->getName());

        $connection = $factory->create('foo', ['driver' => 'pdo_sqlite', 'memory' => true], $config = new Configuration());
        $this->assertNotSame($config, $connection->getConfiguration());
        $this->assertEquals('foo', $connection->getConfiguration()->getName());
        $this->assertEquals('foo', $connection->getName());
    }
}
