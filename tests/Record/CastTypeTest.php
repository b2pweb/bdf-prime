<?php

namespace Bdf\Prime\Record;

use PHPUnit\Framework\TestCase;

class CastTypeTest extends TestCase
{
    public function test_mixed()
    {
        $this->assertSame('foo', CastType::Mixed->cast('foo', false));
        $this->assertSame('foo', CastType::Mixed->cast('foo', true));
        $this->assertSame(123, CastType::Mixed->cast(123, true));
        $this->assertSame($o = new \stdClass(), CastType::Mixed->cast($o, true));
    }

    public function test_integer()
    {
        $this->assertSame(0, CastType::Integer->cast('foo', false));
        $this->assertSame(null, CastType::Integer->cast('foo', true));
        $this->assertSame(0, CastType::Integer->cast('', false));
        $this->assertSame(123, CastType::Integer->cast('123', false));
        $this->assertSame(12, CastType::Integer->cast('12.3', false));
        $this->assertSame(12, CastType::Integer->cast(12.3, false));
        $this->assertSame(null, CastType::Integer->cast(null, true));
        $this->assertSame(0, CastType::Integer->cast(null, false));
    }

    public function test_float()
    {
        $this->assertSame(0.0, CastType::Float->cast('foo', false));
        $this->assertSame(null, CastType::Float->cast('foo', true));
        $this->assertSame(0.0, CastType::Float->cast('', false));
        $this->assertSame(123.0, CastType::Float->cast('123', false));
        $this->assertSame(12.3, CastType::Float->cast('12.3', false));
        $this->assertSame(12.3, CastType::Float->cast(12.3, false));
        $this->assertSame(null, CastType::Float->cast(null, true));
        $this->assertSame(0.0, CastType::Float->cast(null, false));
    }

    public function test_string()
    {
        $this->assertSame('foo', CastType::String->cast('foo', false));
        $this->assertSame('foo', CastType::String->cast('foo', true));
        $this->assertSame('', CastType::String->cast('', false));
        $this->assertSame('123', CastType::String->cast('123', false));
        $this->assertSame('12.3', CastType::String->cast('12.3', false));
        $this->assertSame('12.3', CastType::String->cast(12.3, false));
        $this->assertSame(null, CastType::String->cast(null, true));
        $this->assertSame('', CastType::String->cast(null, false));
    }

    public function test_array()
    {
        $this->assertSame(['foo'], CastType::Array->cast('foo', false));
        $this->assertSame(['foo'], CastType::Array->cast('foo', true));
        $this->assertSame([], CastType::Array->cast('', false));
        $this->assertSame(['123'], CastType::Array->cast('123', false));
        $this->assertSame(['12.3'], CastType::Array->cast('12.3', false));
        $this->assertSame([12.3], CastType::Array->cast(12.3, false));
        $this->assertSame(null, CastType::Array->cast(null, true));
        $this->assertSame([], CastType::Array->cast(null, false));
        $this->assertSame([1, 2, 3], CastType::Array->cast([1, 2, 3], false));
    }

    public function test_boolean()
    {
        $this->assertSame(false, CastType::Boolean->cast('foo', false));
        $this->assertSame(null, CastType::Boolean->cast('foo', true));
        $this->assertSame(false, CastType::Boolean->cast('', false));
        $this->assertSame(false, CastType::Boolean->cast('123', false));
        $this->assertSame(false, CastType::Boolean->cast('12.3', false));
        $this->assertSame(false, CastType::Boolean->cast(12.3, false));
        $this->assertSame(null, CastType::Boolean->cast(12.3, true));
        $this->assertSame(null, CastType::Boolean->cast(null, true));
        $this->assertSame(false, CastType::Boolean->cast(null, false));
        $this->assertSame(true, CastType::Boolean->cast(true, true));
        $this->assertSame(false, CastType::Boolean->cast(false, true));
        $this->assertSame(true, CastType::Boolean->cast(1, true));
        $this->assertSame(true, CastType::Boolean->cast('1', true));
        $this->assertSame(false, CastType::Boolean->cast('0', true));
    }

    public function test_fromType()
    {
        $c = new class {
            public mixed $foo;
            public \DateTime $date;
            public float|int|string $scalar;
            public float $float;
            public int $int;
            public bool $bool;
            public string $str;
            public array $array;
        };
        $r = new \ReflectionClass($c);

        $this->assertSame(CastType::Mixed, CastType::fromType(null));
        $this->assertSame(CastType::Mixed, CastType::fromType($r->getProperty('foo')->getType()));
        $this->assertSame(CastType::Mixed, CastType::fromType($r->getProperty('scalar')->getType()));
        $this->assertSame(CastType::Float, CastType::fromType($r->getProperty('float')->getType()));
        $this->assertSame(CastType::Integer, CastType::fromType($r->getProperty('int')->getType()));
        $this->assertSame(CastType::Boolean, CastType::fromType($r->getProperty('bool')->getType()));
        $this->assertSame(CastType::String, CastType::fromType($r->getProperty('str')->getType()));
        $this->assertSame(CastType::Array, CastType::fromType($r->getProperty('array')->getType()));
    }
}
