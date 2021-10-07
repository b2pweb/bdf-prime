<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SqlBooleanTypeTest extends TestCase
{
    /**
     * @var SqlDateTimeType
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
        $this->type = new SqlBooleanType($this->platform);
    }

    /**
     *
     */
    public function test_declaration()
    {
        $column = $this->createMock(ColumnInterface::class);
        $this->assertEquals(Types::BOOLEAN, $this->type->declaration($column));
    }

    /**
     *
     */
    public function test_from_database()
    {
        $this->assertTrue($this->type->fromDatabase('1'));
        $this->assertTrue($this->type->fromDatabase(true));
        $this->assertFalse($this->type->fromDatabase('0'));
        $this->assertFalse($this->type->fromDatabase(false));
    }

    /**
     *
     */
    public function test_from_database_empty_value()
    {
        $this->assertNull($this->type->fromDatabase(null));
    }

    /**
     *
     */
    public function test_to_database()
    {
        $this->assertEquals('1', $this->type->toDatabase(true));
        $this->assertEquals('0', $this->type->toDatabase(false));
    }

    /**
     *
     */
    public function test_to_database_empty_value()
    {
        $this->assertNull($this->type->toDatabase(null));
    }
}
