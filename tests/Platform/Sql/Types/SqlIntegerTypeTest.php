<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\TypeInterface;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SqlIntegerTypeTest extends TestCase
{
    /**
     * @var SqlIntegerType
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
        $this->type = new SqlIntegerType($this->platform);
    }

    /**
     *
     */
    public function test_declaration()
    {
        $column = $this->createMock(ColumnInterface::class);

        $this->assertEquals(Types::INTEGER, $this->type->declaration($column));
        $this->assertEquals(Types::INTEGER, $this->platform->types()->native(TypeInterface::INTEGER)->declaration($column));
        $this->assertEquals(Types::SMALLINT, $this->platform->types()->native(TypeInterface::SMALLINT)->declaration($column));
        $this->assertEquals(Types::SMALLINT, $this->platform->types()->native(TypeInterface::TINYINT)->declaration($column));
    }

    /**
     *
     */
    public function test_from_database()
    {
        $this->assertSame(1, $this->type->fromDatabase('1'));
        $this->assertSame(1, $this->type->fromDatabase('1.0'));
        $this->assertNull($this->type->fromDatabase(null));
    }

    /**
     *
     */
    public function test_to_database()
    {
        $this->assertSame(1.0, $this->type->toDatabase(1.0));
        $this->assertSame(1, $this->type->toDatabase(1));
        $this->assertNull($this->type->toDatabase(null));
    }
}
