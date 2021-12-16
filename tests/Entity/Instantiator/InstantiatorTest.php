<?php

namespace Bdf\Prime\Entity\Instantiator;

use Bdf\Prime\Entity\InitializableInterface;
use Bdf\Prime\TestEntity;
use Doctrine\Instantiator\Instantiator as DoctrineInstantiator;
use Doctrine\Instantiator\InstantiatorInterface as DoctrineInstantiatorInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 *
 */
class InstantiatorTest extends TestCase
{
    /**
     *
     */
    public function test_set_get_instantiator()
    {
        $doctrine = new DoctrineInstantiator();

        $instantiator = new Instantiator($doctrine);

        $this->assertSame($doctrine, $instantiator->instantiator());
    }

    /**
     *
     */
    public function test_instantiate()
    {
        $instantiator = new Instantiator();

        $object = $instantiator->instantiate(TestEntity::class);

        $this->assertInstanceOf(TestEntity::class, $object);
    }

    /**
     *
     */
    public function test_instantiate_initializable_entity()
    {
        $instantiator = new Instantiator();

        $object = $instantiator->instantiate(IntializedEntity::class);

        $this->assertEquals('initialized', $object->name());
    }

    /**
     *
     */
    public function test_instantiate_with_hint()
    {
        $doctrine = $this->createMock(DoctrineInstantiatorInterface::class);
        $doctrine->expects($this->never())->method('instantiate');

        $instantiator = new Instantiator($doctrine);

        $object = $instantiator->instantiate(TestEntity::class, InstantiatorInterface::USE_CONSTRUCTOR_HINT);

        $this->assertInstanceOf(TestEntity::class, $object);
    }

    /**
     *
     */
    public function test_instantiate_stdclass()
    {
        $instantiator = new Instantiator();

        $object = $instantiator->instantiate(stdClass::class, InstantiatorInterface::USE_CONSTRUCTOR_HINT);

        $this->assertInstanceOf(stdClass::class, $object);
    }
}


class IntializedEntity implements InitializableInterface
{
    private $name;

    /**
     *
     */
    public function initialize(): void
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
