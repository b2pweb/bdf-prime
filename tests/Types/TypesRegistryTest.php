<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Exception\TypeNotFoundException;
use Bdf\Prime\Platform\Sql\Types\SqlIntegerType;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class TypesRegistryTest extends TestCase
{
    /**
     * @var TypesRegistry
     */
    private $registry;


    protected function setUp(): void
    {
        $this->registry = new TypesRegistry();
    }

    /**
     *
     */
    public function test_constructor()
    {
        $registry = new TypesRegistry([
            TypeInterface::TARRAY => ArrayType::class
        ]);

        $this->assertEquals(new ArrayType(), $registry->get(TypeInterface::TARRAY));
    }

    /**
     *
     */
    public function test_register_string()
    {
        $this->registry->register(ArrayType::class, 'array');

        $this->assertInstanceOf(ArrayType::class, $this->registry->get('array'));
        $this->assertSame($this->registry->get('array'), $this->registry->get('array'));
    }

    /**
     *
     */
    public function test_register_instance()
    {
        $this->registry->register($array = new ArrayType(), 'array');
        $this->assertSame($array, $this->registry->get('array'));
    }

    public function test_get_unregistered_type()
    {
        $this->expectException(TypeNotFoundException::class);

        $this->registry->get('not_found');
    }

    /**
     *
     */
    public function test_get_will_create_new_type_with_type_name_on_ctor()
    {
        $this->registry->register(ArrayType::class, 'test');

        $this->assertEquals(new ArrayType('test'), $this->registry->get('test'));
    }

    /**
     *
     */
    public function test_get_will_keep_instance()
    {
        $this->registry->register(ArrayType::class, 'test');

        $this->assertSame($this->registry->get('test'), $this->registry->get('test'));
    }

    /**
     *
     */
    public function test_has()
    {
        $this->assertFalse($this->registry->has('array'));

        $this->registry->register(ArrayType::class, 'array');
        $this->assertTrue($this->registry->has('array'));
    }

    /**
     *
     */
    public function test_has_array_of()
    {
        $this->assertFalse($this->registry->has('integer[]'));

        $this->registry->register(new ArrayType);
        $this->assertFalse($this->registry->has('integer[]'));

        $this->registry->register(new SqlIntegerType(new DummyPlatform()));
        $this->assertTrue($this->registry->has('integer[]'));
        $this->assertTrue($this->registry->has('integer[][][]'));
    }

    /**
     *
     */
    public function test_get_array_of_not_found()
    {
        $this->expectException(TypeNotFoundException::class);

        $this->registry->get('integer[]');
    }

    /**
     *
     */
    public function test_get_array_of()
    {
        $this->registry->register($array = new ArrayType);
        $this->registry->register($integer = new SqlIntegerType(new DummyPlatform()));

        $type = $this->registry->get('integer[]');

        $this->assertEquals(new ArrayOfType($array, $integer), $type);
        $this->assertSame($type, $this->registry->get('integer[]'));
    }

    /**
     *
     */
    public function test_get_array_of_multidimensional()
    {
        $this->registry->register($array = new ArrayType);
        $this->registry->register($integer = new SqlIntegerType(new DummyPlatform()));

        $type = $this->registry->get('integer[][][]');

        $this->assertEquals(
            new ArrayOfType(
                $array,
                new ArrayOfType(
                    $array,
                    new ArrayOfType($array, $integer)
                )
            ),
            $type
        );
    }
}
