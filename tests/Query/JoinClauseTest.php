<?php

namespace Bdf\Prime\Query;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class JoinClauseTest extends TestCase
{
    /**
     * 
     */
    public function test_join_on()
    {
        $join = new JoinClause();
        $join->on('id', 1);
        
        $expected = [
            'column'    => 'id',
            'operator'  => '=',
            'value'     => 1,
            'glue'      => 'AND',
        ];
        
        $this->assertEquals($expected, $join->clauses()[0]);
    }
    
    /**
     * 
     */
    public function test_nested_on()
    {
        $join = new JoinClause();
        $join->on(function($join) {
            $join->on('id', 2);
        }, 'OR');
        
        $expected = [
            'column'    => 'id',
            'operator'  => '=',
            'value'     => 2,
            'glue'      => 'AND',
        ];
        
        $this->assertEquals($expected, $join->clauses()[0]['nested'][0]);
        $this->assertEquals('OR', $join->clauses()[0]['glue']);
    }
    
    /**
     * 
     */
    public function test_join_or_on()
    {
        $join = new JoinClause();
        $join->orOn('id', 1);
        
        $expected = [
            'column'    => 'id',
            'operator'  => '=',
            'value'     => 1,
            'glue'      => 'OR',
        ];
        
        $this->assertEquals($expected, $join->clauses()[0]);
    }
    
    /**
     * 
     */
    public function test_nested_or_on()
    {
        $join = new JoinClause();
        $join->orOn(function($join) {
            $join->orOn('id', 2);
        }, 'AND');
        
        $expected = [
            'column'    => 'id',
            'operator'  => '=',
            'value'     => 2,
            'glue'      => 'OR',
        ];
        
        $this->assertEquals($expected, $join->clauses()[0]['nested'][0]);
        $this->assertEquals('AND', $join->clauses()[0]['glue']);
    }

    /**
     *
     */
    public function test_on_null()
    {
        $join = new JoinClause();
        $join->onNull('id');

        $expected = [
            'column'    => 'id',
            'operator'  => '=',
            'value'     => null,
            'glue'      => 'AND',
        ];

        $this->assertEquals($expected, $join->clauses()[0]);
    }

    /**
     *
     */
    public function test_or_on_null()
    {
        $join = new JoinClause();
        $join->orOnNull('id');

        $expected = [
            'column'    => 'id',
            'operator'  => '=',
            'value'     => null,
            'glue'      => 'OR',
        ];

        $this->assertEquals($expected, $join->clauses()[0]);
    }

    /**
     *
     */
    public function test_on_not_null()
    {
        $join = new JoinClause();
        $join->onNotNull('id');

        $expected = [
            'column'    => 'id',
            'operator'  => '!=',
            'value'     => null,
            'glue'      => 'AND',
        ];

        $this->assertEquals($expected, $join->clauses()[0]);
    }

    /**
     *
     */
    public function test_or_on_not_null()
    {
        $join = new JoinClause();
        $join->orOnNotNull('id');

        $expected = [
            'column'    => 'id',
            'operator'  => '!=',
            'value'     => null,
            'glue'      => 'OR',
        ];

        $this->assertEquals($expected, $join->clauses()[0]);
    }

    /**
     *
     */
    public function test_on_raw()
    {
        $join = new JoinClause();
        $join->onRaw('id = 1');

        $expected = [
            'raw'    => 'id = 1',
            'glue'   => 'AND',
        ];

        $this->assertEquals($expected, $join->clauses()[0]);
    }

    /**
     *
     */
    public function test_or_on_raw()
    {
        $join = new JoinClause();
        $join->orOnRaw('id = 1');

        $expected = [
            'raw'    => 'id = 1',
            'glue'   => 'OR',
        ];

        $this->assertEquals($expected, $join->clauses()[0]);
    }
}