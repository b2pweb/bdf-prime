<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\Types\SqlDateTimeType;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DateTimeTypeTest extends TestCase
{
    /**
     * @var DateTimeType
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
        $this->type = new DateTimeType();
        $this->platform = new DummyPlatform();
    }

    /**
     *
     */
    public function test_config()
    {
        $this->assertNull($this->type->getTimezone());
    }

    /**
     *
     */
    public function test_from_database()
    {
        $this->assertNull($this->type->fromDatabase(null));

        $date = $this->type->fromDatabase('2017-07-10T15:21:35+02:00');
        $this->assertEquals(new \DateTime('2017-07-10 15:21:35'), $date);
        $this->assertEquals('2017-07-10 15:21:35', $date->format('Y-m-d H:i:s'));

        $date = $this->type->fromDatabase('2017-07-10T15:21:35+0200');
        $this->assertEquals(new \DateTime('2017-07-10 15:21:35'), $date);
        $this->assertEquals('2017-07-10 15:21:35', $date->format('Y-m-d H:i:s'));

        $this->assertSame('2017-07-10T15:21:35+02:00', $this->type->toDatabase($date));
    }

    /**
     *
     */
    public function test_from_database_with_options()
    {
        $date = $this->type->fromDatabase('2017-07-10T15:21:35+02:00', [
            'timezone' => 'UTC',
            'className' => \DateTimeImmutable::class,
        ]);

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertEquals('2017-07-10 13:21:35', $date->format('Y-m-d H:i:s'));

        $this->assertSame('2017-07-10T13:21:35+00:00', $this->type->toDatabase($date));
    }

    /**
     *
     */
    public function test_convertToDatabaseValue()
    {
        $this->assertNull($this->type->toDatabase(null));
        $this->assertSame('2017-07-10T15:21:35+02:00', $this->type->toDatabase(new \DateTime('2017-07-10 15:21:35')));
        $this->assertSame('2017-07-10T15:21:35+02:00', $this->type->toDatabase('2017-07-10T15:21:35+02:00'));
    }

    /**
     *
     */
    public function test_to_platform_type()
    {
        $type = $this->type->toPlatformType($this->platform);

        $this->assertInstanceOf(SqlDateTimeType::class, $type);
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
        $this->type = new DateTimeType(TimeType::DATETIME, 'Y-m-d H:i:s', PhpTypeInterface::DATETIME_IMMUTABLE, new \DateTimeZone('UTC'));

        $this->assertSame('UTC', $this->type->getTimezone()->getName());

        $date = $this->type->fromDatabase('2017-07-10 15:21:35');

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertEquals(new \DateTimeImmutable('2017-07-10 15:21:35', new \DateTimeZone('UTC')), $date);
        $this->assertSame('2017-07-10 15:21:35', $this->type->toDatabase($date));
        $this->assertSame('2017-07-10 15:21:35', $date->format('Y-m-d H:i:s'));

        $date = new \DateTime('2017-07-10 15:21:35', new \DateTimeZone('+0200'));
        $this->assertSame('2017-07-10 15:21:35', $this->type->toDatabase($date));
        $this->assertSame('2017-07-10 15:21:35', $date->format('Y-m-d H:i:s'));
        $this->assertSame('2017-07-10T15:21:35+0200', $this->type->toDatabase('2017-07-10T15:21:35+0200'));
    }
}
