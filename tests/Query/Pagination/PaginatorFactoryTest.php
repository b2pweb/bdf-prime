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

        $paginator = PaginatorFactory::instance()->create($query);

        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertSame($query, $paginator->query());
    }

    /**
     *
     */
    public function test_create_walker()
    {
        $query = $this->createMock(SqlQueryInterface::class);

        $paginator = PaginatorFactory::instance()->create($query, 'walker');

        $this->assertInstanceOf(Walker::class, $paginator);
        $this->assertSame($query, $paginator->query());
    }

    /**
     *
     */
    public function test_create_explicit_class_name()
    {
        $query = $this->createMock(SqlQueryInterface::class);

        $paginator = PaginatorFactory::instance()->create($query, EmptyPaginator::class);

        $this->assertInstanceOf(EmptyPaginator::class, $paginator);
    }

    /**
     *
     */
    public function test_instance()
    {
        $this->assertEquals(new PaginatorFactory(), PaginatorFactory::instance());
        $this->assertSame(PaginatorFactory::instance(), PaginatorFactory::instance());
    }

    /**
     *
     */
    public function test_addAlias()
    {
        $query = $this->createMock(SqlQueryInterface::class);

        $factory = new PaginatorFactory();
        $factory->addAlias(EmptyPaginator::class, 'alias');
        $paginator = $factory->create($query, 'alias');

        $this->assertInstanceOf(EmptyPaginator::class, $paginator);
    }

    /**
     *
     */
    public function test_addFactory()
    {
        $query = $this->createMock(SqlQueryInterface::class);

        $factory = new PaginatorFactory();
        $paginator = new EmptyPaginator();
        $factory->addFactory(EmptyPaginator::class, function () use($paginator) { return $paginator; });
        $this->assertSame($paginator, $factory->create($query, EmptyPaginator::class));
    }
}
