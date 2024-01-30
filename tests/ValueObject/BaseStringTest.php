<?php

namespace ValueObject;

use Bdf\Prime\ValueObject\BaseString;
use Bdf\Prime\ValueObject\ValueObjectTypeError;
use PHPUnit\Framework\TestCase;

class BaseStringTest extends TestCase
{
    public function test_from_invalid_type()
    {
        $this->expectException(ValueObjectTypeError::class);
        $this->expectExceptionMessage('Value object Bdf\Prime\ValueObject\BaseString expected to be of type string, bool given');

        BaseString::from(true);
    }

    public function test_from_success()
    {
        $vo = BaseString::from('foo');

        $this->assertSame('foo', $vo->value());
        $this->assertEquals(BaseString::from('foo'), $vo);
    }

    public function test_tryFrom()
    {
        $this->assertNull(BaseString::tryFrom([]));
        $this->assertNull(BaseString::tryFrom(new \stdClass()));
        $this->assertNull(BaseString::tryFrom(null));
        $this->assertEquals(BaseString::from('1'), BaseString::tryFrom(1));
        $this->assertEquals(BaseString::from('1.2'), BaseString::tryFrom(1.2));
        $this->assertEquals(BaseString::from('1'), BaseString::tryFrom(true));
        $this->assertEquals(BaseString::from('foo'), BaseString::tryFrom('foo'));
        $this->assertEquals(BaseString::from('foo'), BaseString::tryFrom(new class { public function __toString() { return 'foo'; } }));
    }
}
