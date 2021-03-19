<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Exception\TypeNotFoundException;

/**
 * Prime types registry
 */
class TypesRegistry implements TypesRegistryInterface
{
    /**
     * Store type instances
     *
     * @var TypeInterface[]|string[]
     */
    private $types = [];


    /**
     * TypesRegistry constructor.
     *
     * @param TypeInterface[]|string[] $types
     */
    public function __construct(array $types = [])
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function register($type, $alias = null)
    {
        if ($alias === null) {
            $alias = $type instanceof TypeInterface ? $type->name() : $type;
        }

        $this->types[$alias] = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (isset($this->types[$name])) {
            if (is_string($this->types[$name])) {
                $class = $this->types[$name];

                return $this->types[$name] = $this->instantiate($class, $name);
            }

            return $this->types[$name];
        }

        if (strpos($name, '[]', -2) !== false) {
            return $this->types[$name] = new ArrayOfType(
                $this->get(TypeInterface::TARRAY),
                $this->get(substr($name, 0, -2))
            );
        }

        throw new TypeNotFoundException($name);
    }

    /**
     * {@inheritdoc}
     */
    public function has($type)
    {
        if (isset($this->types[$type])) {
            return true;
        }

        if (strpos($type, '[]', -2) === false) {
            return false;
        }

        return $this->has(TypeInterface::TARRAY) && $this->has(substr($type, 0, -2));
    }

    /**
     * Instantiate the facade type object
     *
     * @param string $class
     * @param string $name
     *
     * @return TypeInterface
     */
    protected function instantiate($class, $name)
    {
        return new $class($name);
    }
}
