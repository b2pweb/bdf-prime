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
class SqlDecimalTypeTest extends TestCase
{
    /**
     * @var SqlDecimalType
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
        $this->type = new SqlDecimalType($this->platform);
    }

    /**
     *
     */
    public function test_declaration()
    {
        $column = $this->createMock(ColumnInterface::class);
        $this->assertEquals(Types::DECIMAL, $this->type->declaration($column));
    }

    /**
     *
     */
    public function test_from_database()
    {
        $this->assertSame('1', $this->type->fromDatabase('1'));
        $this->assertSame('1.0', $this->type->fromDatabase('1.0'));
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
        $this->assertSame(1.0, $this->type->toDatabase(1.0));
        $this->assertSame(1, $this->type->toDatabase(1));
    }

    /**
     *
     */
    public function test_to_database_empty_value()
    {
        $this->assertNull($this->type->toDatabase(null));
    }
}
