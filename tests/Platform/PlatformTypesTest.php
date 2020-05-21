<?php

namespace Bdf\Prime\Platform;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Exception\TypeException;
use Bdf\Prime\Exception\TypeNotFoundException;
use Bdf\Prime\Platform\Sql\Types\SqlBooleanType;
use Bdf\Prime\Platform\Sql\Types\SqlDateTimeType;
use Bdf\Prime\Platform\Sql\Types\SqlFloatType;
use Bdf\Prime\Platform\Sql\Types\SqlIntegerType;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\Types\ArrayObjectType;
use Bdf\Prime\Types\ArrayOfType;
use Bdf\Prime\Types\ArrayType;
use Bdf\Prime\Types\BooleanType;
use Bdf\Prime\Types\DateTimeType;
use Bdf\Prime\Types\JsonType;
use Bdf\Prime\Types\ObjectType;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesRegistry;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class PlatformTypesTest extends TestCase
{
    /**
     * @var PlatformTypes
     */
    protected $types;

    /**
     * @var PlatformInterface
     */
    protected $platform;


    /**
     *
     */
    protected function setUp(): void
    {
        $this->types = new PlatformTypes(
            $this->platform = new DummyPlatform(),
            [
                TypeInterface::STRING   => SqlStringType::class,
                TypeInterface::INTEGER  => SqlIntegerType::class,
                TypeInterface::FLOAT    => SqlFloatType::class,
                TypeInterface::DOUBLE   => SqlFloatType::class,
                TypeInterface::DATETIME => SqlDateTimeType::class,
                TypeInterface::BOOLEAN  => SqlBooleanType::class,
            ],
            new TypesRegistry([
                TypeInterface::DATETIME => DateTimeType::class,
                TypeInterface::TARRAY   => ArrayType::class,
                TypeInterface::ARRAY_OBJECT => ArrayObjectType::class,
                TypeInterface::JSON     => JsonType::class,
                TypeInterface::OBJECT   => ObjectType::class,
                TypeInterface::BOOLEAN  => BooleanType::class,
            ])
        );
    }

    /**
     *
     */
    public function test_register()
    {
        $this->types->register(SqlStringType::class, 'my_type');
        $this->assertTrue($this->types->isNative('my_type'));
        $this->assertInstanceOf(SqlStringType::class, $this->types->get('my_type'));
    }

    /**
     *
     */
    public function test_resolve()
    {
        $this->assertInstanceOf(SqlDateTimeType::class, $this->types->resolve(new \DateTime()));
        $this->assertInstanceOf(ArrayType::class, $this->types->resolve([]));
        $this->assertInstanceOf(ArrayType::class, $this->types->resolve(['hello', 'world']));
        $this->assertInstanceOf(ArrayObjectType::class, $this->types->resolve(['foo' => 'bar']));
        $this->assertInstanceOf(ObjectType::class, $this->types->resolve((object) ['foo' => 'bar']));
        $this->assertInstanceOf(SqlStringType::class, $this->types->resolve('abcd'));
        $this->assertInstanceOf(SqlIntegerType::class, $this->types->resolve(123));
        $this->assertInstanceOf(SqlFloatType::class, $this->types->resolve(12.3));
        $this->assertInstanceOf(SqlStringType::class, $this->types->resolve(null));
    }

    /**
     *
     */
    public function test_get()
    {
        $this->assertInstanceOf(JsonType::class, $this->types->get(TypeInterface::JSON));
        $this->assertInstanceOf(SqlDateTimeType::class, $this->types->get(TypeInterface::DATETIME));
        $this->assertInstanceOf(SqlIntegerType::class, $this->types->get(TypeInterface::INTEGER));
    }

    /**
     *
     */
    public function test_get_not_found()
    {
        $this->expectException(TypeNotFoundException::class);

        $this->types->get('not_found');
    }

    /**
     *
     */
    public function test_has()
    {
        $this->assertTrue($this->types->has(TypeInterface::JSON));
        $this->assertTrue($this->types->has(TypeInterface::DATETIME));
        $this->assertTrue($this->types->has(TypeInterface::INTEGER));
        $this->assertFalse($this->types->has('not_found'));
    }

    /**
     *
     */
    public function test_native()
    {
        $this->assertInstanceOf(SqlStringType::class, $this->types->native(TypeInterface::JSON));
        $this->assertInstanceOf(SqlStringType::class, $this->types->native(TypeInterface::OBJECT));
        $this->assertInstanceOf(SqlDateTimeType::class, $this->types->native(TypeInterface::DATETIME));
        $this->assertInstanceOf(SqlIntegerType::class, $this->types->native(TypeInterface::INTEGER));
    }

    /**
     *
     */
    public function test_fromDatabase_null()
    {
        $this->assertNull($this->types->fromDatabase(null));
    }

    /**
     *
     */
    public function test_fromDatabase_with_type_string()
    {
        $value = $this->types->fromDatabase('1', 'boolean');

        $this->assertTrue($value);
    }

    /**
     *
     */
    public function test_fromDatabase_with_type_object()
    {
        $type = $this->types->get('integer');
        $value = $this->types->fromDatabase('1', $type);

        $this->assertSame(1, $value);
    }

    /**
     *
     */
    public function test_fromDatabase_with_facade_type()
    {
        $value = $this->types->fromDatabase('{"foo":"bar"}', 'json');

        $this->assertSame(['foo' => 'bar'], $value);
    }

    /**
     *
     */
    public function test_toDatabase_with_type_string()
    {
        $value = $this->types->toDatabase(true, 'boolean');

        $this->assertSame(1, $value);
    }

    /**
     *
     */
    public function test_toDatabase_with_type_object()
    {
        $type = $this->types->get('boolean');
        $value = $this->types->toDatabase(true, $type);

        $this->assertSame(1, $value);
    }

    /**
     *
     */
    public function test_toDatabase_without_type()
    {
        $value = $this->types->toDatabase(true);

        $this->assertSame(1, $value);
    }

    /**
     *
     */
    public function test_toDatabase_with_facade_type()
    {
        $value = $this->types->toDatabase(['foo' => 'bar'], 'json');

        $this->assertSame('{"foo":"bar"}', $value);
    }

    /**
     *
     */
    public function test_toDatabase_null()
    {
        $this->assertNull($this->types->toDatabase(null));
    }

    /**
     *
     */
    public function test_fromDatabase_type_error()
    {
        $this->expectException(TypeException::class);

        $this->types->fromDatabase('my value', false);
    }

    /**
     *
     */
    public function test_toDatabase_type_error()
    {
        $this->expectException(TypeException::class);

        $this->types->toDatabase('my value', false);
    }

    /**
     *
     */
    public function test_isNative()
    {
        $this->assertTrue($this->types->isNative('double'));
        $this->assertFalse($this->types->isNative('array'));
        $this->assertFalse($this->types->isNative('double[]'));
    }

    /**
     *
     */
    public function test_get_will_pass_platform()
    {
        $this->types->register(SqlStringType::class, 'my_type');

        $type = $this->types->get('my_type');
        $this->assertEquals(new SqlStringType($this->platform, 'my_type'), $type);
    }

    /**
     *
     */
    public function test_has_array_of()
    {
        $this->assertFalse($this->types->has('bad_type[]'));
        $this->assertTrue($this->types->has('integer[]'));
        $this->assertTrue($this->types->has('integer[][][]'));
    }

    /**
     *
     */
    public function test_get_array_of_not_found()
    {
        $this->expectException(TypeNotFoundException::class);

        $this->types->get('bad_type[]');
    }

    /**
     *
     */
    public function test_get_array_of()
    {
        $type = $this->types->get('integer[]');

        $this->assertEquals(new ArrayOfType(new ArrayType(), new SqlIntegerType($this->platform)), $type);
        $this->assertSame($type, $this->types->get('integer[]'));
    }

    /**
     *
     */
    public function test_get_array_of_multidimensional()
    {
        $type = $this->types->get('integer[][][]');

        $this->assertEquals(
            new ArrayOfType(
                $this->types->get('array'),
                new ArrayOfType(
                    $this->types->get('array'),
                    new ArrayOfType($this->types->get('array'), $this->types->get('integer'))
                )
            ),
            $type
        );
    }

    /**
     *
     */
    public function test_resolve_unregistered_object()
    {
        $this->assertEquals(new ObjectType(), $this->types->resolve($this));
    }
}
