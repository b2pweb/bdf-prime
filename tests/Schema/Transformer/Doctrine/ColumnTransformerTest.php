<?php

namespace Bdf\Prime\Schema\Transformer\Doctrine;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\Sql\Types\SqlFloatType;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\Schema\Adapter\Doctrine\DoctrineColumn;
use Bdf\Prime\Schema\Bag\Column;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\SchemaAssertion;
use Bdf\Prime\Types\TypeInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ColumnTransformerTest extends TestCase
{
    use SchemaAssertion;

    /**
     * @dataProvider provideColumns
     */
    public function test_toDoctrine_functional(ColumnInterface $column)
    {
        $platform = new DummyPlatform();

        $doctrine = (new ColumnTransformer($column, $platform))->toDoctrine();

        $this->assertInstanceOf(\Doctrine\DBAL\Schema\Column::class, $doctrine);

        $newColumn = new DoctrineColumn($doctrine, $platform->types());

        $this->assertColumns($column, $newColumn);
    }

    /**
     * @return array
     */
    public function provideColumns()
    {
        return [
            'string' => [new Column('col_',   new SqlStringType(new DummyPlatform(), TypeInterface::STRING), null, 32, false, false, false, true)],
            'int'    => [new Column('id_',    new SqlStringType(new DummyPlatform(), TypeInterface::BIGINT), null, null, true, true)],
            'float'  => [new Column('size_',  new SqlFloatType(new DummyPlatform(), TypeInterface::FLOAT), null, null, false, false, false, false, null, 6, 3)],
            'other'  => [new Column('data_',  new SqlStringType(new DummyPlatform(), TypeInterface::BLOB), 'default data', 128, false, false, true, false, 'data comment')],
        ];
    }
}
