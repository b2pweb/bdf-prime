<?php

namespace Bdf\Prime\Schema\Adapter;

use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\IndexInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class NamedIndexTest extends TestCase
{
    /**
     *
     */
    public function test_already_named()
    {
        $index = new NamedIndex(new Index(['field' => []], IndexInterface::TYPE_SIMPLE, 'my_name'), 'table_');

        $this->assertEquals('my_name', $index->name());
    }

    /**
     *
     */
    public function test_generate_name_simple()
    {
        $index = new NamedIndex(new Index(['foo' => [], 'bar' => []]), 'table_');

        $this->assertEquals('IDX_B69F6EC28C73652176FF8CAA', $index->name());
    }

    /**
     *
     */
    public function test_generate_name_unique()
    {
        $index = new NamedIndex(new Index(['foo' => [], 'bar' => []], IndexInterface::TYPE_UNIQUE), 'table_');

        $this->assertEquals('UNIQ_B69F6EC28C73652176FF8CAA', $index->name());
    }

    /**
     *
     */
    public function test_generate_name_primary()
    {
        $index = new NamedIndex(new Index(['foo' => [], 'bar' => []], IndexInterface::TYPE_PRIMARY), 'table_');

        $this->assertEquals('PRIMARY', $index->name());
    }

    /**
     *
     */
    public function test_generate_name_not_a_string()
    {
        $index = new NamedIndex(new Index(['foo' => [], 'bar' => []], Index::TYPE_SIMPLE, 123), 'table_');

        $this->assertEquals('IDX_B69F6EC28C73652176FF8CAA', $index->name());
    }

    /**
     *
     */
    public function test_generate_name_invalid_format()
    {
        $index = new NamedIndex(new Index(['foo' => [], 'bar' => []], Index::TYPE_SIMPLE, '123'), 'table_');

        $this->assertEquals('IDX_B69F6EC28C73652176FF8CAA', $index->name());
    }

    /**
     *
     */
    public function test_fieldOptions()
    {
        $index = new NamedIndex(new Index(['foo' => ['length' => 12], 'bar' => []], Index::TYPE_SIMPLE, '123', []), 'table_');

        $this->assertEquals('IDX_B69F6EC28C73652176FF8CAA', $index->name());
        $this->assertSame(['length' => 12], $index->fieldOptions('foo'));
    }

    /**
     * @dataProvider delegatedMethods
     */
    public function test_delegate_other_methods($method, $return)
    {
        $mock = $this->createMock(IndexInterface::class);

        $index = new NamedIndex($mock, 'table_');

        $mock->expects($this->once())
            ->method($method)
            ->willReturn($return)
        ;

        $this->assertSame($return, $index->$method());
    }

    public function delegatedMethods()
    {
        return [
            ['fields', ['foo', 'bar']],
            ['unique', true],
            ['primary', true],
            ['type', IndexInterface::TYPE_SIMPLE],
            ['isComposite', false],
            ['options', []]
        ];
    }
}
