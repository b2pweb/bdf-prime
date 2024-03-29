<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Exception\TypeNotFoundException;

use function is_string;
use function is_subclass_of;
use function str_ends_with;
use function substr;

/**
 * Prime types registry
 */
class TypesRegistry implements TypesRegistryInterface
{
    /**
     * Store type instances
     *
     * @var array<string, class-string<TypeInterface>|TypeInterface>
     */
    private array $types = [];


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
    public function register($type, ?string $alias = null)
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
    public function get(string $type): TypeInterface
    {
        if (isset($this->types[$type])) {
            if (is_string($this->types[$type])) {
                $class = $this->types[$type];

                return $this->types[$type] = $this->instantiate($class, $type);
            }

            return $this->types[$type];
        }

        if (str_ends_with($type, '[]')) {
            return $this->types[$type] = new ArrayOfType(
                $this->get(TypeInterface::TARRAY),
                $this->get(substr($type, 0, -2))
            );
        }

        if (is_subclass_of($type, FacadeTypeInterface::class)) {
            return $this->types[$type] = $this->instantiate($type, $type);
        }

        throw new TypeNotFoundException($type);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $type): bool
    {
        if (isset($this->types[$type])) {
            return true;
        }

        if (!str_ends_with($type, '[]')) {
            return false;
        }

        return $this->has(TypeInterface::TARRAY) && $this->has(substr($type, 0, -2));
    }

    /**
     * Instantiate the facade type object
     *
     * @param class-string<TypeInterface> $class
     * @param string $name
     *
     * @return TypeInterface
     */
    protected function instantiate($class, $name)
    {
        return new $class($name);
    }
}
