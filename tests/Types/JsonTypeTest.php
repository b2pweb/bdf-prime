<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\PlatformInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class JsonTypeTest extends TestCase
{
    /**
     * @var JsonType
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
        $this->type = new JsonType();
        $this->platform = new DummyPlatform();
    }

    /**
     *
     */
    public function test_fromDatabase()
    {
        $this->assertEquals(['firstName' => 'John', 'lastName' => 'Doe'], $this->type->fromDatabase('{"firstName":"John","lastName":"Doe"}'));
    }

    /**
     *
     */
    public function test_fromDatabase_empty_value()
    {
        $this->assertNull($this->type->fromDatabase(null));
    }

    /**
     *
     */
    public function test_toDatabaseValue()
    {
        $this->assertEquals('["Hello","World","0","!"]', $this->type->toDatabase(['Hello', 'World', '0', '!']));
    }

    /**
     *
     */
    public function test_toDatabaseValue_null()
    {
        $this->assertNull($this->type->toDatabase(null));
    }

    /**
     *
     */
    public function test_php_type()
    {
        $this->assertEquals(PhpTypeInterface::TARRAY, $this->type->phpType());
    }
}
