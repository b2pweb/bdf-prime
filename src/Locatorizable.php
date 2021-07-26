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
     * @var ServiceLocator|Closure
     * @SerializeIgnore  Should be skipped by default as static property.
     */
    private static $locator;


    /**
     * Set the prime service locator
     *
     * @param Closure|ServiceLocator $locator
     */
    final public static function configure($locator)
    {
        self::$locator = $locator;
    }

    /**
     * Get the prime service locator
     *
     * @return ServiceLocator
     */
    final public static function locator()
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
    final public static function isActiveRecordEnabled()
    {
        return self::$locator !== null;
    }
}
