<?php

namespace Bdf\Prime\Clock;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Utility class for convert date time objects
 */
final class Converter
{
    /**
     * Cast a DateTimeImmutable to the given class
     *
     * @param DateTimeImmutable $date
     * @param class-string<T> $class The target class
     * @return T
     *
     * @template T as DateTimeInterface
     */
    public static function castToClass(DateTimeImmutable $date, string $class): DateTimeInterface
    {
        if ($date instanceof $class) {
            return $date;
        }

        if (is_subclass_of($class, DateTime::class) || $class === DateTime::class) {
            /** @var T */
            return $class::createFromImmutable($date);
        }

        /** @psalm-suppress UndefinedMethod */
        return $class::createFromInterface($date);
    }
}
