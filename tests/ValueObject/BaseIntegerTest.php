<?php

namespace ValueObject;

use Bdf\Prime\ValueObject\BaseInteger;
use Bdf\Prime\ValueObject\ValueObjectTypeError;
use PHPUnit\Framework\TestCase;

class BaseIntegerTest extends TestCase
{
    public function test_from_invalid_type()
    {
        $this->expectException(ValueObjectTypeError::class);
        $this->expectExceptionMessage('Value object Bdf\Prime\ValueObject\BaseInteger expected to be of type int, bool given');

        try {
            BaseInteger::from(true);
        } catch (ValueObjectTypeError $e) {
            $this->assertSame('Bdf\Prime\ValueObject\BaseInteger', $e->type());
            $this->assertSame('int', $e->expectedPrimitiveType());
            $this->assertSame('bool', $e->actualPrimitiveType());

            throw $e;
        }
    }

    public function test_from_success()
    {
        $vo = BaseInteger::from(123);

        $this->assertSame(123, $vo->value());
        $this->assertEquals(BaseInteger::from(123), $vo);
    }

    public function test_tryFrom()
    {
        $this->assertNull(BaseInteger::tryFrom([]));
        $this->assertNull(BaseInteger::tryFrom(new \stdClass()));
        $this->assertNull(BaseInteger::tryFrom(null));
        $this->assertEquals(BaseInteger::from(1), BaseInteger::tryFrom('1'));
        $this->assertEquals(BaseInteger::from(1), BaseInteger::tryFrom(1.2));
        $this->assertEquals(BaseInteger::from(1), BaseInteger::tryFrom(true));
        $this->assertEquals(BaseInteger::from(0), BaseInteger::tryFrom('foo'));
    }
}
