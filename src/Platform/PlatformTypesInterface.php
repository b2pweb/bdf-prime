<?php

namespace Bdf\Prime\Platform;

use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesRegistryInterface;

/**
 * Handle platform types with common types
 *
 * Add an abstraction layer for handle platform types with common types
 * - TypesRegistryInterface method should be used only for transformation purpose, not declarative (fromDatabase, toDatabase)
 * - For declaration purpose, use native() or registry()
 */
interface PlatformTypesInterface extends TypesRegistryInterface
{
    /**
     * Check if the given type is nativelly supported by the current platform
     *
     * @param string $name The type name
     *
     * @return bool
     */
    public function isNative(string $name): bool;

    /**
     * Get the native type related to the name
     * This method is useful for get real platform type from ORM
     *
     * @param string $name The type name. Can be a common type or a platform type
     *
     * @return PlatformTypeInterface
     */
    public function native(string $name): PlatformTypeInterface;

    /**
     * Resolve the best type from a PHP value
     * The returned type can only be used for conversion purpose, not for declaration
     *
     * @param mixed $value Value to resolve
     *
     * @return TypeInterface|null The type, or null to let connection decide
     */
    public function resolve($value): ?TypeInterface;

    /**
     * Convert a PHP value to database value
     *
     * @param mixed $value Value to convert
     * @param string|TypeInterface|null $type The type. Can be the type name (string), the type instance, or null to resolve the type
     *
     * @return mixed
     *
     * @see PlatformTypesInterface::fromDatabase()
     */
    public function toDatabase($value, $type = null);

    /**
     * Convert a database value to PHP value
     *
     * @param mixed $value
     * @param string|TypeInterface|null $type The type. Can be the type name (string), the type instance, or null to resolve the type
     * @param array $fieldOptions
     *
     * @return mixed
     *
     * @see PlatformTypesInterface::toDatabase()
     */
    public function fromDatabase($value, $type = null, array $fieldOptions = []);
}
