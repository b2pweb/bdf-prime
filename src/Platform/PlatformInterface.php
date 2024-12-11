<?php

namespace Bdf\Prime\Platform;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Interface for Prime connection platforms
 *
 * @method mixed apply(PlatformSpecificOperationInterface $operation)
 */
interface PlatformInterface
{
    /**
     * Get the platform name
     *
     * @return string
     * @deprecated Since 2.2. Use {@see PlatformInterface::apply()} to discriminate platform.
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
     * @internal This method should not be used by end user. Use {@see PlatformInterface::apply()} instead.
     */
    public function grammar();

    /**
     * Apply the operation on the platform
     *
     * The platform will try to find the correct operation to apply.
     * If the operation do not support the platform, {@see PlatformSpecificOperationInterface::onUnknownPlatform()} will be called.
     *
     * @param PlatformSpecificOperationInterface<R> $operation
     * @return R The value returned by the operation
     *
     * @template R
     */
    //public function apply(PlatformSpecificOperationInterface $operation);
}
