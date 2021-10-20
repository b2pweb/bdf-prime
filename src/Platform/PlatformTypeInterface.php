<?php

namespace Bdf\Prime\Platform;

use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\TypeInterface;

/**
 * Interface for platform native specific types
 */
interface PlatformTypeInterface extends TypeInterface
{
    /**
     * PlatformTypeInterface constructor.
     *
     * @param PlatformInterface $platform
     * @param string $name
     */
    public function __construct(PlatformInterface $platform, string $name);

    /**
     * Get the field declaration for the platform
     *
     * @param ColumnInterface $column  The column schema
     *
     * @return mixed
     */
    public function declaration(ColumnInterface $column);
}
