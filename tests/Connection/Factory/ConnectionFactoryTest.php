<?php

namespace Bdf\Prime\Connection\Factory;

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
}
