<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\Types\SqlDateType;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DateTypeTest extends TestCase
{
    /**
     * @var DateType
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
        $this->type = new DateType();
        $this->platform = new DummyPlatform();
    }

    /**
     *
     */
    public function test_from_database()
    {
        $this->assertNull($this->type->fromDatabase(null));
        $this->assertEquals(new \DateTime('2017-07-10 00:00:00'), $this->type->fromDatabase('2017-07-10'));
    }

    /**
     *
     */
    public function test_convertToDatabaseValue()
    {
        $this->assertNull($this->type->toDatabase(null));
        $this->assertSame('2017-07-10', $this->type->toDatabase(new \DateTime('2017-07-10 15:21:35')));
    }

    /**
     *
     */
    public function test_to_platform_type()
    {
        $type = $this->type->toPlatformType($this->platform);

        $this->assertInstanceOf(SqlDateType::class, $type);
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
        $this->type = new DateType(TimeType::DATE, \DateTimeImmutable::class, new \DateTimeZone('UTC'));

        $date = $this->type->fromDatabase('2017-07-10');

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertEquals(new \DateTimeImmutable('2017-07-10 00:00:00', new \DateTimeZone('UTC')), $date);
        $this->assertSame('2017-07-10', $this->type->toDatabase($date));
    }
}
