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
    public function get($type)
    {
        if (isset($this->types[$type])) {
            if (is_string($this->types[$type])) {
                $class = $this->types[$type];

                return $this->types[$type] = $this->instantiate($class, $type);
            }

            return $this->types[$type];
        }

        if (strpos($type, '[]', -2) !== false) {
            return $this->types[$type] = new ArrayOfType(
                $this->get(TypeInterface::TARRAY),
                $this->get(substr($type, 0, -2))
            );
        }

        throw new TypeNotFoundException($type);
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
