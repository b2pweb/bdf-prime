<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\Types\SqlIntegerType;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class TimestampTypeTest extends TestCase
{
    /**
     * @var TimestampType
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
        $this->type = new TimestampType();
        $this->platform = new DummyPlatform();
    }

    /**
     *
     */
    public function test_from_database()
    {
        $this->assertEquals(new \DateTime('2017-09-04 10:18:24'), $this->type->fromDatabase(1504513104));
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
    public function test_convertToDatabaseValue()
    {
        $this->assertSame(1504513104, $this->type->toDatabase(new \DateTime('2017-09-04 10:18:24')));
        $this->assertNull($this->type->toDatabase(null));
        $this->assertSame(123456, $this->type->toDatabase(123456));
    }

    /**
     *
     */
    public function test_to_platform_type()
    {
        $type = $this->type->toPlatformType($this->platform);

        $this->assertInstanceOf(SqlIntegerType::class, $type);
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
    public function test_immutable()
    {
        $this->type = new TimestampType(TimeType::TIMESTAMP, \DateTimeImmutable::class);

        $date = $this->type->fromDatabase(1504513104);

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertEquals('2017-09-04 08:18:24', $date->format('Y-m-d H:i:s'));
        $this->assertSame(1504513104, $this->type->toDatabase($date));
    }

    /**
     *
     */
    public function test_fromDatabase_with_timezone_options_should_set_the_timezone()
    {
        $this->type = new TimestampType(TimeType::TIMESTAMP, \DateTimeImmutable::class);

        $date = $this->type->fromDatabase(1504513104, [
            'timezone' => '+0200',
            'className' => \DateTimeImmutable::class,
        ]);

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertEquals('2017-09-04 10:18:24', $date->format('Y-m-d H:i:s'));
        $this->assertSame(1504513104, $this->type->toDatabase($date));
    }
}
