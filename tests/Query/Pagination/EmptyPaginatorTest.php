<?php

namespace Bdf\Prime\Query\Pagination;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class EmptyPaginatorTest extends TestCase
{
    /**
     * 
     */
    public function test_pagination_wrapper()
    {
        $paginator = new EmptyPaginator();
        
        $this->assertEquals(0, $paginator->size());
        $this->assertEquals(0, $paginator->count());
        $this->assertEquals(1, $paginator->page());
        $this->assertEquals(0, $paginator->offset());
        $this->assertEquals(0, $paginator->limit());
        $this->assertEquals(0, $paginator->pageMaxRows());
        $this->assertEquals('', $paginator->order());
    }
    
    /**
     * 
     */
    public function test_paginator_supports_collection_interface()
    {
        $paginator = new EmptyPaginator();
        
        $this->assertTrue($paginator->isEmpty());
        $this->assertNull($paginator->get('test'));
        
        $paginator->clear();
        $paginator->map(function(){});
        $paginator->filter(function(){});
    }
}