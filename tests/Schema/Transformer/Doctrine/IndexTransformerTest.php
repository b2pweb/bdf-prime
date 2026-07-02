<?php

namespace Bdf\Prime\Schema\Transformer\Doctrine;

use Bdf\Prime\Schema\Adapter\Doctrine\DoctrineIndex;
use Bdf\Prime\Schema\Adapter\Doctrine\DoctrinePrimaryKeyIndex;
use Bdf\Prime\Schema\Adapter\NamedIndex;
use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\SchemaAssertion;
use Doctrine\DBAL\Schema\Index\IndexedColumn;
use Doctrine\DBAL\Schema\Index\IndexType;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use PHPUnit\Framework\TestCase;

use function array_map;

/**
 *
 */
class IndexTransformerTest extends TestCase
{
    use SchemaAssertion;

    /**
     * @dataProvider provideIndexes
     */
    public function test_toDoctrine(IndexInterface $index, bool $primary = false)
    {
        $doctrine = new IndexTransformer($index)->toDoctrine();

        $this->assertInstanceOf(\Doctrine\DBAL\Schema\Index::class, $doctrine);
        $this->assertIndex($index, new DoctrineIndex($doctrine, $primary));
    }

    public function test_toDoctrinePrimaryKey()
    {
        $doctrine = new IndexTransformer(new Index(['id_' => []], Index::TYPE_PRIMARY, 'PRIMARY'))->toDoctrinePrimaryKey();

        $this->assertInstanceOf(PrimaryKeyConstraint::class, $doctrine);
        $this->assertIndex(new Index(['id_' => []], Index::TYPE_PRIMARY, 'PRIMARY'), new DoctrinePrimaryKeyIndex($doctrine));
    }

    /**
     *
     */
    public function provideIndexes()
    {
        return [
            'simple'   => [new Index(['name_' => []], Index::TYPE_SIMPLE, 'NAME')],
            'multiple' => [new Index(['first_name' => [], 'last_name' => []], Index::TYPE_SIMPLE, 'MULTIPLE')],
            'primary'  => [new Index(['id_' => []], Index::TYPE_PRIMARY, 'PRIMARY'), true],
            'unique'   => [new Index(['first_name' => [], 'last_name' => []], Index::TYPE_UNIQUE, 'UNIQUE')],
            'named'    => [new NamedIndex(new Index(['email_' => []], Index::TYPE_UNIQUE), 'table_')],
            'options'  => [new NamedIndex(new Index(['email_' => []], Index::TYPE_SIMPLE, null, ['fulltext' => true]), 'table_')],
            'fieldOptions' => [new NamedIndex(new Index(['email_' => ['length' => 12]], Index::TYPE_SIMPLE, null, []), 'table_')],
        ];
    }

    /**
     *
     */
    public function test_toDoctrine_with_options_and_flags()
    {
        $doctrine = new IndexTransformer(new NamedIndex(new Index(['email_' => []], Index::TYPE_SIMPLE, null, ['fulltext' => true, 'where' => 'xxx']), 'tbl'))->toDoctrine();

        $this->assertSame(IndexType::FULLTEXT, $doctrine->getType());
        $this->assertSame('xxx', $doctrine->getPredicate());
    }

    /**
     *
     */
    public function test_toDoctrine_with_field_length_option()
    {
        $doctrine = (new IndexTransformer(new NamedIndex(new Index(['name_' => [], 'email_' => ['length' => 12]], Index::TYPE_SIMPLE, null, []), 'tbl')))->toDoctrine();

        $this->assertSame([null, 12], array_map(fn (IndexedColumn $c) => $c->getLength(), $doctrine->getIndexedColumns()));
    }
}
