<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\PlatformTypeInterface;

/**
 * Interface for ORM types (i.e. non-platform specifics)
 */
interface FacadeTypeInterface extends TypeInterface
{
    /**
     * Get the platform type related to this type
     *
     * @param PlatformInterface $platform
     *
     * @return PlatformTypeInterface
     */
    public function toPlatformType(PlatformInterface $platform): PlatformTypeInterface;
}
