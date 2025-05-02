<?php

namespace Bdf\Prime\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Base implementation of the ClockInterface using the native PHP functions
 * This is a fallback implementation, so if you want a more flexible clock, you should get a library providing "psr/clock" implementation
 */
final class NativeClock implements ClockInterface
{
    private static ?NativeClock $instance = null;

    /**
     * {@inheritdoc}
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    /**
     * Get the instance of the clock
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }
}
