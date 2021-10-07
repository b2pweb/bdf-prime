<?php

namespace Bdf\Prime\Connection\Result;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ArrayResultSetTest extends TestCase
{
    /**
     * @var ArrayResultSet
     */
    private $resultSet;

    protected function setUp(): void
    {
        $this->resultSet = new ArrayResultSet([
            [
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ],
            [
                'first_name' => 'John',
                'last_name'  => 'Smith'
            ],
            [
                'first_name' => 'Donald',
                'last_name'  => 'Duck'
            ],
        ]);
    }

    /**
     *
     */
    public function test_values()
    {
        $this->assertSame(3, count($this->resultSet));
        $this->assertTrue($this->resultSet->isRead());
        $this->assertFalse($this->resultSet->isWrite());
        $this->assertFalse($this->resultSet->hasWrite());
    }

    /**
     *
     */
    public function test_all()
    {
        $this->assertEquals([
            [
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ],
            [
                'first_name' => 'John',
                'last_name'  => 'Smith'
            ],
            [
                'first_name' => 'Donald',
                'last_name'  => 'Duck'
            ],
        ], $this->resultSet->all());

        $this->assertEquals([
            [
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ],
            [
                'first_name' => 'John',
                'last_name'  => 'Smith'
            ],
            [
                'first_name' => 'Donald',
                'last_name'  => 'Duck'
            ],
        ], $this->resultSet->asAssociative()->all());

        $this->assertEquals([
            [
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ],
            [
                'first_name' => 'John',
                'last_name'  => 'Smith'
            ],
            [
                'first_name' => 'Donald',
                'last_name'  => 'Duck'
            ],
        ], $this->resultSet->fetchMode(ResultSetInterface::FETCH_ASSOC)->all());
    }

    /**
     *
     */
    public function test_all_num()
    {
        $this->assertEquals([
            ['John', 'Doe'],
            ['John', 'Smith'],
            ['Donald', 'Duck'],
        ], $this->resultSet->fetchMode(ResultSetInterface::FETCH_NUM)->all());
        $this->assertEquals([
            ['John', 'Doe'],
            ['John', 'Smith'],
            ['Donald', 'Duck'],
        ], $this->resultSet->asList()->all());
    }

    /**
     *
     */
    public function test_all_column()
    {
        $this->assertEquals(['Doe', 'Smith', 'Duck'], $this->resultSet->asColumn(1)->all());
        $this->assertEquals(['John', 'John', 'Donald'], $this->resultSet->fetchMode(ResultSetInterface::FETCH_COLUMN)->all());
        $this->assertEquals(['Doe', 'Smith', 'Duck'], $this->resultSet->fetchMode(ResultSetInterface::FETCH_COLUMN, 1)->all());
    }

    /**
     *
     */
    public function test_all_object()
    {
        $this->assertEquals([
            (object) [
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ],
            (object) [
                'first_name' => 'John',
                'last_name'  => 'Smith'
            ],
            (object) [
                'first_name' => 'Donald',
                'last_name'  => 'Duck'
            ],
        ], $this->resultSet->asObject()->all());
        $this->assertEquals([
            (object) [
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ],
            (object) [
                'first_name' => 'John',
                'last_name'  => 'Smith'
            ],
            (object) [
                'first_name' => 'Donald',
                'last_name'  => 'Duck'
            ],
        ], $this->resultSet->fetchMode(ResultSetInterface::FETCH_OBJECT)->all());
    }

    /**
     *
     */
    public function test_all_class()
    {
        $this->assertEquals([
            new ArrayResultTestClass('John', 'Doe'),
            new ArrayResultTestClass('John', 'Smith'),
            new ArrayResultTestClass('Donald', 'Duck'),
        ], $this->resultSet->asClass(ArrayResultTestClass::class)->all());
        $this->assertEquals([
            new ArrayResultTestClass('John', 'Doe'),
            new ArrayResultTestClass('John', 'Smith'),
            new ArrayResultTestClass('Donald', 'Duck'),
        ], $this->resultSet->fetchMode(ResultSetInterface::FETCH_CLASS, ArrayResultTestClass::class)->all());
    }

    /**
     *
     */
    public function test_current()
    {
        $this->assertEquals([
            'first_name' => 'John',
            'last_name'  => 'Doe'
        ], $this->resultSet->current());
    }

    /**
     *
     */
    public function test_current_object()
    {
        $this->assertEquals((object) [
            'first_name' => 'John',
            'last_name'  => 'Doe'
        ], $this->resultSet->asObject()->current());

        $this->resultSet->next();
        $this->resultSet->next();
        $this->resultSet->next();

        $this->assertFalse($this->resultSet->current());
    }

    /**
     *
     */
    public function test_current_class()
    {
        $this->assertEquals(new ArrayResultTestClass('John', 'Doe'), $this->resultSet->asClass(ArrayResultTestClass::class)->current());

        $this->resultSet->next();
        $this->resultSet->next();
        $this->resultSet->next();

        $this->assertFalse($this->resultSet->current());
    }

    /**
     *
     */
    public function test_current_list()
    {
        $this->assertEquals(['John', 'Doe'], $this->resultSet->asList()->current());

        $this->resultSet->next();
        $this->resultSet->next();
        $this->resultSet->next();

        $this->assertFalse($this->resultSet->current());
    }

    /**
     *
     */
    public function test_current_column()
    {
        $this->resultSet->fetchMode(ResultSetInterface::FETCH_COLUMN);

        $this->assertEquals('John', $this->resultSet->current());

        $this->resultSet->asColumn()->rewind();

        $this->assertEquals('John', $this->resultSet->current());
    }

    /**
     *
     */
    public function test_iterator()
    {
        $this->assertEquals([
            [
                'first_name' => 'John',
                'last_name'  => 'Doe'
            ],
            [
                'first_name' => 'John',
                'last_name'  => 'Smith'
            ],
            [
                'first_name' => 'Donald',
                'last_name'  => 'Duck'
            ],
        ], iterator_to_array($this->resultSet));
    }
}

class ArrayResultTestClass
{
    private $first_name;
    private $last_name;

    public function __construct($first_name = null, $last_name = null)
    {
        $this->first_name = $first_name;
        $this->last_name = $last_name;
    }
}