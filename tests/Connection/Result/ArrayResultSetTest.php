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
    }

    /**
     *
     */
    public function test_all_column()
    {
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
    public function test_current_column()
    {
        $this->resultSet->fetchMode(ResultSetInterface::FETCH_COLUMN);

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