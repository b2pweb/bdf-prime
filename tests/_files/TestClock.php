<?php

namespace _files;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

class TestClock implements ClockInterface
{
    public static ?DateTimeImmutable $current = null;

    /**
     * {@inheritdoc}
     */
    public function now(): DateTimeImmutable
    {
        return self::$current ?? new DateTimeImmutable();
    }

    public static function set(DateTimeImmutable $current): void
    {
        self::$current = $current;
    }

    public static function reset(): void
    {
        self::$current = null;
    }
}
