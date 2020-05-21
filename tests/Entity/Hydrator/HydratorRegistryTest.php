<?php

namespace Bdf\Prime\Entity\Hydrator;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class HydratorRegistryTest extends TestCase
{
    /**
     * @var HydratorRegistry
     */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->registry = new HydratorRegistry();
    }

    /**
     *
     */
    public function test_add()
    {
        $hydrator = $this->createMock(HydratorInterface::class);
        $this->registry->add('Entity', $hydrator);

        $this->assertSame($hydrator, $this->registry->get('Entity'));
    }

    /**
     *
     */
    public function test_set_hydrators()
    {
        $hydrator = $this->createMock(HydratorInterface::class);
        $this->registry->setHydrators(['Entity' => $hydrator]);

        $this->assertSame($hydrator, $this->registry->get('Entity'));
    }

    /**
     *
     */
    public function test_factory()
    {
        $hydrator = $this->createMock(HydratorInterface::class);
        $factory = function() use($hydrator) {
            return $hydrator;
        };

        $this->registry->factory('Entity', $factory);

        $this->assertSame($hydrator, $this->registry->get('Entity'));
    }

    /**
     *
     */
    public function test_set_factories()
    {
        $hydrator = $this->createMock(HydratorInterface::class);
        $factory = function() use($hydrator) {
            return $hydrator;
        };

        $this->registry->setFactories(['Entity' => $factory]);

        $this->assertSame($hydrator, $this->registry->get('Entity'));
    }

    /**
     *
     */
    public function test_get_default_base_hydrator()
    {
        $this->assertInstanceOf(ArrayHydrator::class, $this->registry->get('unknown'));
    }

    /**
     *
     */
    public function test_get_registered_base_hydrator()
    {
        $hydrator = $this->createMock(HydratorInterface::class);
        $this->registry->setBaseHydrator($hydrator);

        $this->assertSame($hydrator, $this->registry->get('unknown'));
    }

    /**
     *
     */
    public function test_get_registered_instance()
    {
        $hydrator = new ArrayHydrator();
        $this->registry->add('Entity', $hydrator);

        $this->assertSame($hydrator, $this->registry->get('Entity'));
    }

    /**
     *
     */
    public function test_get_registered_factory()
    {
        $called = false;

        $factory = function() use(&$called) {
            $called = true;
            return new ArrayHydrator();
        };

        $this->registry->factory('Entity', $factory);

        $this->assertInstanceOf(ArrayHydrator::class, $this->registry->get('Entity'));
        $this->assertTrue($called);

        $called = false;
        $this->registry->get('Entity');
        $this->assertFalse($called);
    }
}