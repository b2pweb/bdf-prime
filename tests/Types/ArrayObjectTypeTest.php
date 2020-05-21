<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ArrayObjectTypeTest extends TestCase
{
    /**
     * @var ArrayObjectType
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
        $this->type = new ArrayObjectType();
        $this->platform = new DummyPlatform();
    }

    /**
     *
     */
    public function test_fromDatabase()
    {
        $this->assertNull($this->type->fromDatabase(null));
        $this->assertEquals([], $this->type->fromDatabase('a:0:{}'));
    }

    /**
     *
     */
    public function test_toDatabase()
    {
        $this->assertNull($this->type->toDatabase(null));
        $this->assertEquals('a:0:{}', $this->type->toDatabase([]));
        $this->assertSame('a:0:{}', $this->type->toDatabase('a:0:{}'));
    }

    /**
     *
     */
    public function test_to_platform_type()
    {
        $type = $this->type->toPlatformType($this->platform);

        $this->assertInstanceOf(SqlStringType::class, $type);
    }

    /**
     *
     */
    public function test_php_type()
    {
        $this->assertEquals(PhpTypeInterface::TARRAY, $this->type->phpType());
    }
}
