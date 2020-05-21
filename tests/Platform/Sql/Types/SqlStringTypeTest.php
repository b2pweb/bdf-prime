<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\TypeInterface;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SqlStringTypeTest extends TestCase
{
    /**
     * @var SqlStringType
     */
    protected $type;

    /**
     * @var PlatformInterface
     */
    protected $platform;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->platform = new DummyPlatform();
        $this->type = new SqlStringType($this->platform);
    }

    /**
     *
     */
    public function test_declaration()
    {
        $column = $this->createMock(ColumnInterface::class);

        $this->assertEquals(Type::STRING, $this->type->declaration($column));
        $this->assertEquals(Type::STRING, $this->platform->types()->native(TypeInterface::STRING)->declaration($column));
        $this->assertEquals(Type::TEXT, $this->platform->types()->native(TypeInterface::TEXT)->declaration($column));
        $this->assertEquals(Type::BIGINT, $this->platform->types()->native(TypeInterface::BIGINT)->declaration($column));
        $this->assertEquals(Type::BINARY, $this->platform->types()->native(TypeInterface::BINARY)->declaration($column));
        $this->assertEquals(Type::BLOB, $this->platform->types()->native(TypeInterface::BLOB)->declaration($column));
    }

    /**
     *
     */
    public function test_from_database()
    {
        $this->assertSame('foo', $this->type->fromDatabase('foo'));
        $this->assertNull($this->type->fromDatabase(null));
        $this->assertSame('1', $this->type->fromDatabase(1.0));
    }

    /**
     *
     */
    public function test_to_database()
    {
        $this->assertSame('1', $this->type->toDatabase(1.0));
        $this->assertSame('foo', $this->type->toDatabase('foo'));
        $this->assertNull($this->type->toDatabase(null));
    }
}