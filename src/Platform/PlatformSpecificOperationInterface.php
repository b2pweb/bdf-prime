<?php

namespace Bdf\Prime\Platform;

/**
 * Type for an operation which depends on a specific platform
 * Use this type when the operation is implemented differently on each platform (e.g. different SQL syntax between MySQL and SQLite)
 *
 * All methods of this type takes as first parameter the platform instance, and as second parameter the grammar instance,
 * and return the result of the operation, of type "R" as template
 *
 * Note: do not use directly this interface, but use specific one for the target platform
 *
 * @template R
 */
interface PlatformSpecificOperationInterface
{
    /**
     * Fallback method when the actual platform cannot be found, or is not supported by the operation
     *
     * @param PlatformInterface $platform
     * @param object $grammar
     *
     * @return R
     */
    public function onUnknownPlatform(PlatformInterface $platform, object $grammar);
}
