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
    public function register($type, $alias = null);

    /**
     * Get the type object from its name
     *
     * @param string $type
     *
     * @return TypeInterface
     */
    public function get($type);

    /**
     * Check if the registry has the requested type
     *
     * @param string $type
     *
     * @return boolean
     */
    public function has($type);
}
