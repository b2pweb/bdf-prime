<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\Types\SqlBooleanType;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class BooleanTypeTest extends TestCase
{
    /**
     * @var BooleanType
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
        $this->type = new BooleanType();
        $this->platform = new DummyPlatform();
    }

    /**
     *
     */
    public function test_convertToPHPValue()
    {
        $this->assertNull($this->type->fromDatabase(null));
        $this->assertTrue($this->type->fromDatabase('true'));
        $this->assertTrue($this->type->fromDatabase('1'));
        $this->assertFalse($this->type->fromDatabase('0'));
    }

    /**
     *
     */
    public function test_convertToDatabaseValue()
    {
        $this->assertSame('true', $this->type->toDatabase(true));
        $this->assertSame('false', $this->type->toDatabase(false));
        $this->assertNull($this->type->toDatabase(null));
    }

    /**
     *
     */
    public function test_to_platform_type()
    {
        $type = $this->type->toPlatformType($this->platform);

        $this->assertInstanceOf(SqlBooleanType::class, $type);
    }

    /**
     *
     */
    public function test_php_type()
    {
        $this->assertEquals(PhpTypeInterface::BOOLEAN, $this->type->phpType());
    }
}
