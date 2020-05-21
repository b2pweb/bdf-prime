<?php

namespace Bdf\Prime\Query\Pagination;

use Bdf\Prime\Query\SqlQueryInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class PaginatorFactoryTest extends TestCase
{
    /**
     *
     */
    public function test_create_default()
    {
        $query = $this->createMock(SqlQueryInterface::class);

        $paginator = PaginatorFactory::create($query);

        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertSame($query, $paginator->query());
    }

    /**
     *
     */
    public function test_create_walker()
    {
        $query = $this->createMock(SqlQueryInterface::class);

        $paginator = PaginatorFactory::create($query, 'walker');

        $this->assertInstanceOf(Walker::class, $paginator);
        $this->assertSame($query, $paginator->query());
    }

    /**
     *
     */
    public function test_create_explicit_class_name()
    {
        $query = $this->createMock(SqlQueryInterface::class);

        $paginator = PaginatorFactory::create($query, EmptyPaginator::class);

        $this->assertInstanceOf(EmptyPaginator::class, $paginator);
    }
}
