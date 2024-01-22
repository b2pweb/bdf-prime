<?php

namespace Connection\Middleware\Explain;

use Bdf\Prime\Connection\Middleware\Explain\QueryType;
use PHPUnit\Framework\TestCase;

class QueryTypeTest extends TestCase
{
    public function test_worst()
    {
        $this->assertEquals(QueryType::SCAN, QueryType::worst(QueryType::SCAN, QueryType::INDEX));
        $this->assertEquals(QueryType::SCAN, QueryType::worst(QueryType::INDEX, QueryType::SCAN));
        $this->assertEquals(QueryType::INDEX, QueryType::worst(QueryType::INDEX, QueryType::PRIMARY));
        $this->assertEquals(QueryType::INDEX, QueryType::worst(QueryType::INDEX, QueryType::CONST));
        $this->assertEquals(QueryType::SCAN, QueryType::worst(QueryType::UNDEFINED, QueryType::SCAN));
        $this->assertEquals(QueryType::INDEX, QueryType::worst(QueryType::INDEX, QueryType::UNDEFINED));
        $this->assertEquals(QueryType::UNDEFINED, QueryType::worst(QueryType::UNDEFINED, QueryType::UNDEFINED));
    }

    public function test_isSlower()
    {
        $this->assertTrue(QueryType::isSlower(QueryType::SCAN, QueryType::INDEX));
        $this->assertFalse(QueryType::isSlower(QueryType::INDEX, QueryType::SCAN));
        $this->assertTrue(QueryType::isSlower(QueryType::INDEX, QueryType::PRIMARY));
        $this->assertTrue(QueryType::isSlower(QueryType::PRIMARY, QueryType::CONST));
        $this->assertFalse(QueryType::isSlower(QueryType::PRIMARY, QueryType::PRIMARY));
        $this->assertFalse(QueryType::isSlower(QueryType::SCAN, QueryType::UNDEFINED));
        $this->assertFalse(QueryType::isSlower(QueryType::UNDEFINED, QueryType::SCAN));
        $this->assertFalse(QueryType::isSlower(QueryType::UNDEFINED, QueryType::UNDEFINED));
    }
}
