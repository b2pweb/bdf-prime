<?php

namespace Bdf\Prime\Schema\Adapter\Doctrine;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\PlatformTypesInterface;
use Bdf\Prime\Platform\Sql\Types\SqlFloatType;
use Bdf\Prime\Platform\Sql\Types\SqlIntegerType;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\PrimeTestCase;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DoctrineColumnTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var PlatformTypesInterface
     */
    protected $types;

    /**
     * @var PlatformInterface
     */
    protected $platform;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->platform = $this->prime()->connection('test')->platform();
        $this->types = $this->platform->types();
    }

    /**
     *
     */
    public function test_for_string()
    {
        $column = new DoctrineColumn(
            new Column('col_name', Type::getType(Types::STRING), ['length' => 60, 'notnull' => false, 'comment' => 'my comment', 'default' => 'my_default']),
            $this->types
        );

        $this->assertEquals('col_name', $column->name());
        $this->assertEquals(new SqlStringType($this->platform), $column->type());
        $this->assertEquals('my_default', $column->defaultValue());
        $this->assertEquals(60, $column->length());
        $this->assertTrue($column->nillable());
        $this->assertEquals('my comment', $column->comment());
    }

    /**
     *
     */
    public function test_for_int()
    {
        $column = new DoctrineColumn(
            new Column('col_name', Type::getType(Types::INTEGER), ['unsigned' => true, 'autoincrement' => true]),
            $this->types
        );

        $this->assertEquals('col_name', $column->name());
        $this->assertEquals(new SqlIntegerType($this->platform), $column->type());
        $this->assertTrue($column->unsigned());
        $this->assertTrue($column->autoIncrement());
    }

    /**
     *
     */
    public function test_for_float()
    {
        $column = new DoctrineColumn(
            new Column('col_name', Type::getType(Types::FLOAT), ['precision' => 5, 'scale' => 2]),
            $this->types
        );

        $this->assertEquals('col_name', $column->name());
        $this->assertEquals(new SqlFloatType($this->platform), $column->type());
        $this->assertEquals(5, $column->precision());
        $this->assertEquals(2, $column->scale());
    }

    /**
     *
     */
    public function test_options()
    {
        $column = new DoctrineColumn(
            $doctrine = new Column('col_name', Type::getType(Types::FLOAT), ['precision' => 5, 'scale' => 2]),
            $this->types
        );

        $doctrine->setPlatformOptions(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $column->options());
        $this->assertEquals('bar', $column->option('foo'));
    }
}
