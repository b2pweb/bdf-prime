<?php

namespace Bdf\Prime\Clock;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

use function is_subclass_of;
use function method_exists;

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

        // DateTime::createFromImmutable() return static type only since PHP 8.0
        // So in PHP 7.4, this method can only be called if the target class is DateTime
        // Else we must use the constructor (last case)
        if ((PHP_MAJOR_VERSION >= 8 && is_subclass_of($class, DateTime::class)) || $class === DateTime::class) {
            /** @var T */
            return $class::createFromImmutable($date);
        }

        // Exists since PHP 8.0
        if (method_exists($class, 'createFromInterface')) {
            /** @psalm-suppress UndefinedMethod */
            return $class::createFromInterface($date);
        }

        // Handle microseconds and ensure that the timezone is same as the original date
        // To remove when PHP 7.4 support will be dropped
        return (new $class($date->format('Y-m-d\TH:i:s.uP')))->setTimezone($date->getTimezone());
    }
}
