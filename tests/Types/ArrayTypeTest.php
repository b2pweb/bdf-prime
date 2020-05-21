<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ArrayTypeTest extends TestCase
{
    /**
     * @var ArrayType
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
        $this->type = new ArrayType();
        $this->platform = new DummyPlatform();
    }

    /**
     *
     */
    public function test_fromDatabase_will_filter()
    {
        $this->assertSame([], $this->type->fromDatabase(null));
        $this->assertSame(['Hello', 'World', '0', '!'], $this->type->fromDatabase(',Hello,World,,0,!,'));
    }

    /**
     *
     */
    public function test_toDatabaseValue()
    {
        $this->assertNull($this->type->toDatabase(null));
        $this->assertSame('', $this->type->toDatabase([]));
        $this->assertSame(',Hello,World,0,!,', $this->type->toDatabase(['Hello', 'World', '0', '!']));
        $this->assertSame(',Hello,World,0,!,', $this->type->toDatabase(',Hello,World,0,!,'));
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
        $this->assertSame(PhpTypeInterface::TARRAY, $this->type->phpType());
    }
}
