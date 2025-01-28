<?php

namespace Bus\Query\ReturnType;

use Bdf\Prime\Bus\Query\ReturnType\PaginatorType;
use Bdf\Prime\Query\Pagination\EmptyPaginator;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

class PaginatorTypeTest extends TestCase
{
    public function test()
    {
        $t = PaginatorType::of(User::class);

        $this->assertSame(User::class, $t->unwrappedType());

        $p = new EmptyPaginator();
        $this->assertSame($p, $t->cast($p));
    }
}