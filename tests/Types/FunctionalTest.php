<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Platform\PlatformTypes;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class FunctionalTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var PlatformTypes
     */
    private $types;


    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->types = $this->prime()->connection('test')->platform()->types();
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeReset();
    }

    /**
     * @dataProvider typesProvider
     */
    public function test_to_from_database($name, $value)
    {
        $type = $this->types->get($name);

        $this->assertEquals($value, $type->fromDatabase($type->toDatabase($value)));
        $this->assertEquals($name, $type->name());
    }

    /**
     *
     */
    public function typesProvider()
    {
        return [
            [TypeInterface::TARRAY,     ['foo', 'bar']],
            [TypeInterface::JSON,       ['foo' => 'bar']],
            [TypeInterface::OBJECT,     (object) ['foo' => 'bar']],
            [TypeInterface::BOOLEAN,    true],
            [TypeInterface::BOOLEAN,    false],
            [TypeInterface::TINYINT,    5],
            [TypeInterface::SMALLINT,   652],
            [TypeInterface::INTEGER,    14587],
            [TypeInterface::BIGINT,     '455741'],
            [TypeInterface::DOUBLE,     1.23],
            [TypeInterface::FLOAT,      1.23],
            [TypeInterface::DECIMAL,    1.23],
            [TypeInterface::STRING,     'azerty'],
            [TypeInterface::TEXT,       'azerty'],
            [TypeInterface::BLOB,       'azerty'],
            [TypeInterface::BINARY,     'azerty'],
            [TypeInterface::GUID,       '123'],
            [TypeInterface::DATETIME,   new \DateTime('2017-08-25 12:14:23')],
            [TypeInterface::DATETIMETZ, new \DateTime('2017-08-25 12:14:23')],
            [TypeInterface::DATE,       new \DateTime('2017-08-25')],
            [TypeInterface::TIME,       \DateTime::createFromFormat('!H:i:s', '12:14:23')],
            [TypeInterface::TIMESTAMP,  new \DateTime('2017-08-25 12:14:23')],
            ['date_utc',  new \DateTimeImmutable('2017-08-25 12:14:23', new \DateTimeZone('UTC'))],
        ];
    }
}
