<?php

namespace Bus\Query\ReturnType;

use Bdf\Prime\Bus\Query\ReturnType\ArrayType;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

class ArrayTypeTest extends TestCase
{
    public function test()
    {
        $t = ArrayType::of(User::class);

        $this->assertSame(User::class, $t->unwrappedType());
        $this->assertSame([], $t->cast(null));
    }
}
