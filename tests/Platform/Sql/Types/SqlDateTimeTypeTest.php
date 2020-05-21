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
class SqlDateTimeTypeTest extends TestCase
{
    /**
     * @var SqlDateTimeType
     */
    private $type;

    /**
     * @var PlatformInterface
     */
    private $platform;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->platform = new DummyPlatform();
        $this->type = new SqlDateTimeType($this->platform);
    }

    /**
     *
     */
    public function test_declaration()
    {
        $column = $this->createMock(ColumnInterface::class);
        $this->assertEquals(Type::DATETIME, $this->type->declaration($column));
    }

    /**
     *
     */
    public function test_from_database()
    {
        $this->assertNull($this->type->fromDatabase(null));
        $this->assertNull($this->type->fromDatabase('0000-00-00 00:00:00'));
        $this->assertEquals(new \DateTime('2017-07-10T15:21:35+0200'), $this->type->fromDatabase('2017-07-10 15:21:35'));
    }

    /**
     *
     */
    public function test_to_database()
    {
        $this->assertNull($this->type->toDatabase(null));
        $this->assertSame('2017-07-10 15:21:35', $this->type->toDatabase(new \DateTime('2017-07-10T15:21:35+0200')));
    }

    /**
     *
     */
    public function test_utc_immutable()
    {
        $this->type = new SqlDateTimeType($this->platform, SqlDateTimeType::DATETIME, \DateTimeImmutable::class, new \DateTimeZone('UTC'));

        $date = $this->type->fromDatabase('2017-07-10 15:21:35');

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertEquals(new \DateTimeImmutable('2017-07-10 15:21:35', new \DateTimeZone('UTC')), $date);
        $this->assertSame('2017-07-10 15:21:35', $this->type->toDatabase($date));

        $date = new \DateTime('2017-07-10 15:21:35', new \DateTimeZone('+0200'));
        $this->assertSame('2017-07-10 15:21:35', $this->type->toDatabase($date));
        $this->assertSame('2017-07-10T15:21:35+0200', $this->type->toDatabase('2017-07-10T15:21:35+0200'));
    }
}