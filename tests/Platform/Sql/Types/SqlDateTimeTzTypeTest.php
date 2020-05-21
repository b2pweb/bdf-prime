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
class SqlDateTimeTzTypeTest extends TestCase
{
    /**
     * @var SqlDateTimeTzType
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
        $this->type = new SqlDateTimeTzType($this->platform);
    }

    /**
     *
     */
    public function test_declaration()
    {
        $column = $this->createMock(ColumnInterface::class);
        $this->assertEquals(Type::DATETIMETZ, $this->type->declaration($column));
    }

    /**
     *
     */
    public function test_from_database()
    {
        $this->assertNull($this->type->fromDatabase(null));
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
    public function test_immutable()
    {
        $this->type = new SqlDateTimeTzType($this->platform, SqlDateTimeTzType::DATETIMETZ, \DateTimeImmutable::class);

        $date = $this->type->fromDatabase('2017-07-10 15:21:35');

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertEquals(new \DateTimeImmutable('2017-07-10T15:21:35+0200'), $date);
        $this->assertSame('2017-07-10 15:21:35', $this->type->toDatabase($date));
    }
}