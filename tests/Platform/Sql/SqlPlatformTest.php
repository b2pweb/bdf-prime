<?php

namespace Bdf\Prime\Platform\Sql;

use Bdf\Prime\Platform\PlatformTypes;
use Bdf\Prime\Platform\Sql\Types\SqlBooleanType;
use Bdf\Prime\Platform\Sql\Types\SqlDateTimeType;
use Bdf\Prime\Platform\Sql\Types\SqlFloatType;
use Bdf\Prime\Platform\Sql\Types\SqlIntegerType;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesRegistry;
use DateTime;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SqlPlatformTest extends TestCase
{
    /**
     * @var SqlPlatform
     */
    protected $platform;


    /**
     *
     */
    public function setUp(): void
    {
        $this->platform = new SqlPlatform(new MySQLPlatform(), new TypesRegistry());
    }

    /**
     *
     */
    public function test_name()
    {
        $this->assertEquals('mysql', $this->platform->name());
    }

    /**
     *
     */
    public function test_grammar()
    {
        $this->assertInstanceOf(MySQLPlatform::class, $this->platform->grammar());
    }

    /**
     *
     */
    public function test_types()
    {
        /** @var PlatformTypes $types */
        $types = $this->platform->types();

        $this->assertInstanceOf(PlatformTypes::class, $types);

        $this->assertInstanceOf(SqlDateTimeType::class, $types->get('datetime'));
        $this->assertInstanceOf(SqlFloatType::class, $types->get('double'));
        $this->assertInstanceOf(SqlStringType::class, $types->get('string'));
        $this->assertInstanceOf(SqlStringType::class, $types->get('text'));
        $this->assertInstanceOf(SqlIntegerType::class, $types->get('integer'));
        $this->assertInstanceOf(SqlIntegerType::class, $types->get('smallint'));
        $this->assertInstanceOf(SqlBooleanType::class, $types->get('boolean'));
    }

    /**
     *
     */
    public function test_datetime()
    {
        $this->assertEquals('2017-07-10 15:35:15', $this->platform->types()->get('datetime')->toDatabase(new DateTime('2017-07-10 15:35:15')));
        $this->assertEquals(new DateTime('2017-07-10 15:35:15'), $this->platform->types()->get('datetime')->fromDatabase('2017-07-10 15:35:15'));
    }

    /**
     *
     */
    public function test_integer()
    {
        $this->assertEquals(123, $this->platform->types()->get('integer')->toDatabase(123));
        $this->assertEquals(123, $this->platform->types()->get('integer')->fromDatabase('123'));
    }

    /**
     *
     */
    public function test_string()
    {
        $this->assertEquals(123, $this->platform->types()->get('string')->toDatabase(123));
        $this->assertEquals('123', $this->platform->types()->get('string')->fromDatabase('123'));
    }

    /**
     *
     */
    public function test_double()
    {
        $this->assertEqualsWithDelta(12.3, $this->platform->types()->get('double')->toDatabase(12.3), .00001);
        $this->assertEqualsWithDelta(12.3, $this->platform->types()->get('double')->fromDatabase('12.3'), .00001);
    }

    /**
     *
     */
    public function test_boolean()
    {
        $this->assertEquals(1, $this->platform->types()->get('boolean')->toDatabase(true));
        $this->assertEquals(0, $this->platform->types()->get('boolean')->toDatabase(false));
        $this->assertEquals(false, $this->platform->types()->get('boolean')->fromDatabase('0'));
        $this->assertEquals(true, $this->platform->types()->get('boolean')->fromDatabase('1'));
    }

    /**
     * @dataProvider typesName
     *
     * @param string $type
     */
    public function test_type_null($type)
    {
        $this->assertNull($this->platform->types()->get($type)->fromDatabase(null));
        $this->assertNull($this->platform->types()->get($type)->toDatabase(null));
        $this->assertEquals($type, $this->platform->types()->get($type)->name());
    }

    /**
     * @return array
     */
    public function typesName()
    {
        return [
            [TypeInterface::INTEGER],
            [TypeInterface::STRING],
            [TypeInterface::DOUBLE],
            [TypeInterface::FLOAT],
            [TypeInterface::DATETIME],
            [TypeInterface::BOOLEAN],
        ];
    }
}
