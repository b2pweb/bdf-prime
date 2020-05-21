<?php

namespace Bdf\Prime\Schema\Transformer\Doctrine;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Adapter\Doctrine\DoctrineTable;
use Bdf\Prime\Schema\Bag\Column;
use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\Bag\Table;
use Bdf\Prime\SchemaAssertion;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesRegistry;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class TableTransformerTest extends TestCase
{
    use SchemaAssertion;
    use PrimeTestCase;

    /**
     *
     */
    public function test_toDoctrine_with_DoctrineTable()
    {
        $doctrine = $this->createMock(\Doctrine\DBAL\Schema\Table::class);
        $table = new DoctrineTable($doctrine, new TypesRegistry());

        $this->assertSame($doctrine, (new TableTransformer($table, new DummyPlatform()))->toDoctrine());
    }

    /**
     *
     */
    public function test_toDoctrine_unit()
    {
        $this->primeStart();
        $platform = $this->prime()->connection('test')->platform();
        $table = new Table(
            'table_',
            [
                new Column('id_', new SqlStringType($platform, TypeInterface::BIGINT), null, null, true),
                new Column('name_', new SqlStringType($platform, TypeInterface::STRING), null, 32)
            ],
            new IndexSet([
                new Index(['id_' => []], Index::TYPE_PRIMARY, 'PRIMARY')
            ])
        );

        $doctrine = (new TableTransformer($table, $platform))->toDoctrine();

        $this->assertInstanceOf(\Doctrine\DBAL\Schema\Table::class, $doctrine);
        $this->assertEquals('table_', $doctrine->getName());
        $this->assertEquals(['id_'], $doctrine->getPrimaryKey()->getColumns());

        $this->assertTable($table, new DoctrineTable($doctrine, $platform->types()));
    }
}
