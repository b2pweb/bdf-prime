<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SqlGuidTypeTest extends TestCase
{
    /**
     * @var SqlGuidType
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
        $this->type = new SqlGuidType($this->platform);
    }

    /**
     *
     */
    public function test_declaration()
    {
        $column = $this->createMock(ColumnInterface::class);
        $this->assertEquals(Types::GUID, $this->type->declaration($column));
    }
}
