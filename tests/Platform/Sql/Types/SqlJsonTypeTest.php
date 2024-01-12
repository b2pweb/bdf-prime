<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Exception\TypeException;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\TypeInterface;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SqlJsonTypeTest extends TestCase
{
    protected SqlJsonType $type;
    protected PlatformInterface $platform;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->platform = new DummyPlatform();
        $this->type = new SqlJsonType($this->platform, TypeInterface::JSON);
    }

    /**
     *
     */
    public function test_declaration_default()
    {
        $column = $this->createMock(ColumnInterface::class);

        $this->assertEquals(Types::TEXT, $this->type->declaration($column));
    }

    /**
     *
     */
    public function test_declaration_native_type()
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->expects($this->once())->method('options')->willReturn(['use_native_json' => true]);

        $this->assertEquals(Types::JSON, $this->type->declaration($column));
    }

    /**
     *
     */
    public function test_declaration_text_type()
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->expects($this->once())->method('options')->willReturn(['use_native_json' => false]);

        $this->assertEquals(Types::TEXT, $this->type->declaration($column));
    }

    /**
     *
     */
    public function test_from_database()
    {
        $this->assertSame(1, $this->type->fromDatabase('1'));
        $this->assertSame(1.0, $this->type->fromDatabase('1.0'));
        $this->assertNull($this->type->fromDatabase(null));
        $this->assertSame(['foo', 'bar'], $this->type->fromDatabase('["foo", "bar"]'));
        $this->assertSame(['foo' => 'bar'], $this->type->fromDatabase('{"foo":"bar"}'));
        $this->assertSame('foo', $this->type->fromDatabase('"foo"'));
        $this->assertEquals((object) ['foo' => 'bar'], $this->type->fromDatabase('{"foo":"bar"}', ['object_as_array' => false]));
    }

    /**
     *
     */
    public function test_from_database_invalid_json()
    {
        $this->expectException(TypeException::class);
        $this->expectExceptionMessage('Invalid JSON data : Syntax error');

        $this->type->fromDatabase('invalid');
    }

    /**
     *
     */
    public function test_to_database()
    {
        $this->assertSame('1', $this->type->toDatabase(1.0));
        $this->assertSame('1.2', $this->type->toDatabase(1.2));
        $this->assertSame('1', $this->type->toDatabase(1));
        $this->assertNull($this->type->toDatabase(null));
        $this->assertSame('["foo","bar"]', $this->type->toDatabase(['foo', 'bar']));
        $this->assertSame('{"foo":"bar"}', $this->type->toDatabase(['foo' => 'bar']));
        $this->assertSame('"foo"', $this->type->toDatabase('foo'));
    }
}
