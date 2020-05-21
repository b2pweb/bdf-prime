<?php

namespace Bdf\Prime\Entity\Instantiator;

use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class RegistryInstantiatorTest extends TestCase
{
    /**
     *
     */
    public function test_default_instantiator()
    {
        $instantiator = new RegistryInstantiator();

        $this->assertInstanceOf(Instantiator::class, $instantiator->getDefaultInstantiator());
    }

    /**
     *
     */
    public function test_set_get_default_instantiator()
    {
        $default = $this->createMock(InstantiatorInterface::class);

        $instantiator = new RegistryInstantiator($default);

        $this->assertSame($default, $instantiator->getDefaultInstantiator());
    }

    /**
     *
     */
    public function test_default_instantiate()
    {
        $instantiator = new RegistryInstantiator();

        $object = $instantiator->instantiate(TestEntity::class);

        $this->assertInstanceOf(TestEntity::class, $object);
    }

    /**
     *
     */
    public function test_custom_instantiate()
    {
        $instantiator = new RegistryInstantiator();
        $instantiator->register(TestEntity::class, new TestEntityInstantiator());

        $object = $instantiator->instantiate(TestEntity::class);

        $this->assertEquals(1, $object->id);
    }
}


class TestEntityInstantiator implements InstantiatorInterface
{
    public function instantiate($className, $hint = null)
    {
        return new TestEntity([
            'id' => 1
        ]);
    }
}