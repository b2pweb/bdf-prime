<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SqlTimeTypeTest extends TestCase
{
    /**
     * @var SqlTimeType
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
        $this->type = new SqlTimeType($this->platform);
    }

    /**
     *
     */
    public function test_declaration()
    {
        $column = $this->createMock(ColumnInterface::class);
        $this->assertEquals(Type::TIME, $this->type->declaration($column));
    }

    /**
     *
     */
    public function test_from_database()
    {
        $this->assertNull($this->type->fromDatabase(null));
        $this->assertEquals(new \DateTime('1970-01-01T15:21:35'), $this->type->fromDatabase('15:21:35'));
    }

    /**
     *
     */
    public function test_to_database()
    {
        $this->assertNull($this->type->toDatabase(null));
        $this->assertSame('15:21:35', $this->type->toDatabase(new \DateTime('2017-07-10T15:21:35+0200')));
    }

    /**
     *
     */
    public function test_utc_immutable()
    {
        $this->type = new SqlTimeType($this->platform, SqlTimeType::TIME, \DateTimeImmutable::class, new \DateTimeZone('UTC'));

        $date = $this->type->fromDatabase('15:21:35');

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertEquals(new \DateTimeImmutable('1970-01-01 15:21:35', new \DateTimeZone('UTC')), $date);
        $this->assertSame('15:21:35', $this->type->toDatabase($date));
    }
}