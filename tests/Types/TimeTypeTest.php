<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\Types\SqlTimeType;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class TimeTypeTest extends TestCase
{
    /**
     * @var TimeType
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
        $this->type = new TimeType();
        $this->platform = new DummyPlatform();
    }

    /**
     *
     */
    public function test_from_database()
    {
        $this->assertNull($this->type->fromDatabase(null));
        $this->assertEquals(new \DateTime('1970-01-01 15:21:35'), $this->type->fromDatabase('15:21:35'));
    }

    /**
     *
     */
    public function test_convertToDatabaseValue()
    {
        $this->assertNull($this->type->toDatabase(null));
        $this->assertSame('15:21:35', $this->type->toDatabase(new \DateTime('2017-07-10 15:21:35')));
    }

    /**
     *
     */
    public function test_to_platform_type()
    {
        $type = $this->type->toPlatformType($this->platform);

        $this->assertInstanceOf(SqlTimeType::class, $type);
    }

    /**
     *
     */
    public function test_php_type()
    {
        $this->assertEquals(PhpTypeInterface::DATETIME, $this->type->phpType());
    }

    /**
     *
     */
    public function test_utc_immutable()
    {
        $this->type = new TimeType(TimeType::TIME, \DateTimeImmutable::class, new \DateTimeZone('UTC'));

        $date = $this->type->fromDatabase('15:21:35');

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertEquals(new \DateTimeImmutable('1970-01-01 15:21:35', new \DateTimeZone('UTC')), $date);
        $this->assertSame('15:21:35', $this->type->toDatabase($date));
    }
}
