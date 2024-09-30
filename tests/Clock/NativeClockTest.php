<?php

namespace Clock;

use Bdf\Prime\Clock\NativeClock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class NativeClockTest extends TestCase
{
    public function test_instance()
    {
        $this->assertSame(NativeClock::instance(), NativeClock::instance());
    }

    public function test_now()
    {
        $this->assertEquals(new DateTimeImmutable(), NativeClock::instance()->now());
        $this->assertNotSame(NativeClock::instance()->now(), NativeClock::instance()->now());
        $this->assertSame(DateTimeImmutable::class, get_class(NativeClock::instance()->now()));
    }
}
