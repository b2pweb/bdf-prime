<?php

namespace Bdf\Prime\Schema\Transformer\Doctrine;

use Bdf\Prime\Schema\Adapter\Doctrine\DoctrineIndex;
use Bdf\Prime\Schema\Adapter\NamedIndex;
use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\SchemaAssertion;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class IndexTransformerTest extends TestCase
{
    use SchemaAssertion;

    /**
     * @dataProvider provideIndexes
     */
    public function test_toDoctrine(IndexInterface $index)
    {
        $doctrine = (new IndexTransformer($index))->toDoctrine();

        $this->assertInstanceOf(\Doctrine\DBAL\Schema\Index::class, $doctrine);
        $this->assertIndex($index, new DoctrineIndex($doctrine));
    }

    /**
     *
     */
    public function provideIndexes()
    {
        return [
            'simple'   => [new Index(['name_' => []], Index::TYPE_SIMPLE, 'NAME')],
            'multiple' => [new Index(['first_name' => [], 'last_name' => []], Index::TYPE_SIMPLE, 'MULTIPLE')],
            'primary'  => [new Index(['id_' => []], Index::TYPE_PRIMARY, 'PRIMARY')],
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
        $doctrine = (new IndexTransformer(new NamedIndex(new Index(['email_' => []], Index::TYPE_SIMPLE, null, ['fulltext' => true, 'my_option' => 'val']), 'tbl')))->toDoctrine();

        $this->assertSame(['fulltext'], $doctrine->getFlags());
        $this->assertSame(['my_option' => 'val'], $doctrine->getOptions());
    }

    /**
     *
     */
    public function test_toDoctrine_with_field_length_option()
    {
        $doctrine = (new IndexTransformer(new NamedIndex(new Index(['name_' => [], 'email_' => ['length' => 12]], Index::TYPE_SIMPLE, null, []), 'tbl')))->toDoctrine();

        $this->assertSame([null, 12], $doctrine->getOption('lengths'));
    }
}
