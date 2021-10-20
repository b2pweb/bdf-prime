<?php

namespace Bdf\Prime\Platform;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Interface for Prime connection platforms
 */
interface PlatformInterface
{
    /**
     * Get the platform name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get the platform dependant types
     *
     * Types used by related compiler SHOULD be present into.
     * This types will be used by {@link FacadeType}, or by compiler when used without ORM
     *
     * @return PlatformTypesInterface
     */
    public function types(): PlatformTypesInterface;

    /**
     * Get the platform grammar instance
     *
     * @return AbstractPlatform
     */
    public function grammar();
}
