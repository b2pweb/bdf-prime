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
class SqlDateTypeTest extends TestCase
{
    /**
     * @var SqlDateType
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
        $this->type = new SqlDateType($this->platform);
    }

    /**
     *
     */
    public function test_declaration()
    {
        $column = $this->createMock(ColumnInterface::class);
        $this->assertEquals(Type::DATE, $this->type->declaration($column));
    }

    /**
     *
     */
    public function test_from_database()
    {
        $this->assertNull($this->type->fromDatabase(null));
        $this->assertEquals(new \DateTime('2017-07-10T00:00:00'), $this->type->fromDatabase('2017-07-10'));
    }

    /**
     *
     */
    public function test_to_database()
    {
        $this->assertNull($this->type->toDatabase(null));
        $this->assertSame('2017-07-10', $this->type->toDatabase(new \DateTime('2017-07-10T15:21:35+0200')));
    }

    /**
     *
     */
    public function test_utc_immutable()
    {
        $this->type = new SqlDateType($this->platform, SqlDateType::DATE, \DateTimeImmutable::class, new \DateTimeZone('UTC'));

        $date = $this->type->fromDatabase('2017-07-10');

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertEquals(new \DateTimeImmutable('2017-07-10 00:00:00', new \DateTimeZone('UTC')), $date);
        $this->assertSame('2017-07-10', $this->type->toDatabase($date));
    }
}