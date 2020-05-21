<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ObjectTypeTest extends TestCase
{
    /**
     * @var ObjectType
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
        $this->type = new ObjectType();
        $this->platform = new DummyPlatform();
    }

    /**
     *
     */
    public function test_fromDatabase()
    {
        $this->assertNull($this->type->fromDatabase(null));
        $this->assertEquals(new \stdClass(), $this->type->fromDatabase('O:8:"stdClass":0:{}'));
    }

    /**
     *
     */
    public function test_toDatabase()
    {
        $this->assertNull($this->type->toDatabase(null));
        $this->assertEquals('O:8:"stdClass":0:{}', $this->type->toDatabase(new \stdClass()));
        $this->assertEquals('O:8:"stdClass":0:{}', $this->type->toDatabase('O:8:"stdClass":0:{}'));
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
        $this->assertEquals(PhpTypeInterface::OBJECT, $this->type->phpType());
    }
}
