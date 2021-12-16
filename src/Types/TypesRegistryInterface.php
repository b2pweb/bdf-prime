<?php

namespace Bdf\Prime\Types;

/**
 * Interface for register and get Prime types
 */
interface TypesRegistryInterface
{
    /**
     * Register a new type
     *
     * @param string|TypeInterface $type
     * @param null|string $alias The type alias name
     *
     * @return $this
     */
    public function register($type, ?string $alias = null);

    /**
     * Get the type object from its name
     *
     * @param string $type
     *
     * @return TypeInterface
     */
    public function get(string $type): TypeInterface;

    /**
     * Check if the registry has the requested type
     *
     * @param string $type
     *
     * @return bool
     */
    public function has(string $type): bool;
}
