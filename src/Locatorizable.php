<?php

namespace Bdf\Prime;

use Closure;

/**
 * Locatorizable
 *
 * @IgnoreAnnotation("SerializeIgnore")
 */
abstract class Locatorizable
{
    /**
     * @var ServiceLocator|Closure():ServiceLocator|null
     * @SerializeIgnore  Should be skipped by default as static property.
     */
    private static $locator;


    /**
     * Set the prime service locator
     *
     * @param Closure():ServiceLocator|ServiceLocator $locator
     *
     * @return void
     */
    final public static function configure($locator): void
    {
        self::$locator = $locator;
    }

    /**
     * Get the prime service locator
     *
     * @return ServiceLocator|null
     *
     * @psalm-ignore-nullable-return
     */
    final public static function locator(): ?ServiceLocator
    {
        // check if locator is a resolver
        if (self::$locator instanceof Closure) {
            $callback = self::$locator;
            self::$locator = $callback();
        }

        return self::$locator;
    }

    /**
     * Check if active record is enabled
     *
     * @return bool
     */
    final public static function isActiveRecordEnabled(): bool
    {
        return self::$locator !== null;
    }
}
