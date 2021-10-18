<?php

namespace Bdf\Prime\Entity\Hydrator;

use Bdf\Prime\Entity\Hydrator\Exception\InvalidTypeException;
use Bdf\Prime\Entity\ImportableInterface;
use Closure;
use stdClass;

use TypeError;
use function get_class;
use function is_array;
use function method_exists;
use function property_exists;
use function ucfirst;

/**
 * Base hydrator implementation.
 * Works like {@link ArrayHydrator}
 */
class ArrayHydrator implements HydratorInterface
{
    /**
     * Array prefix for protected properties
     */
    const PROTECTED_PREFIX = "\0*\0";

    /**
     * @var array<class-string, callable(object, array)>
     */
    private $hydratorsCache = [];

    /**
     * {@inheritdoc}
     */
    public function hydrate($object, array $data): void
    {
        $this->getHydratorForClass(get_class($object))($object, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function extract($object, array $attributes = []): array
    {
        $values = [];
        $attributes = array_flip($attributes);

        $privatePrefix = "\0" . get_class($object) . "\0";
        $privatePrefixLen = strlen($privatePrefix);

        foreach ((array) $object as $name => $property) {
            if (strpos($name, self::PROTECTED_PREFIX) === 0) {
                $name = substr($name, 3);
            } elseif (strpos($name, $privatePrefix) === 0) {
                $name = substr($name, $privatePrefixLen);
            }

            if (!empty($attributes) && !isset($attributes[$name])) {
                continue;
            }

            if ($property instanceof ImportableInterface) {
                $values[$name] = $property->export();
            } else {
                $values[$name] = $property;
            }
        }

        return $values;
    }

    /**
     * Create or retrieve the hydrator callback for the given entity class
     *
     * @param class-string $entityClass The entity class name
     *
     * @return callable(object, array):void
     */
    private function getHydratorForClass(string $entityClass): callable
    {
        if (isset($this->hydratorsCache[$entityClass])) {
            return $this->hydratorsCache[$entityClass];
        }

        $hydrator = static function ($object, array $data): void {
            foreach ($data as $property => $value) {
                try {
                    if (isset($object->$property) && $object->$property instanceof ImportableInterface && is_array($value)) {
                        $object->$property->import($value);
                    } elseif (method_exists($object, 'set' . ucfirst($property))) {
                        $object->{'set' . ucfirst($property)}($value);
                    } elseif (property_exists($object, $property)) {
                        $object->$property = $value;
                    }
                } catch (TypeError $e) {
                    throw new InvalidTypeException($e);
                }
            }
        };

        if ($entityClass !== stdClass::class) {
            // Bind to access private properties
            // Note: ignore stdClass because all its fields are public
            $hydrator = Closure::bind($hydrator, null, $entityClass);
        }

        return $this->hydratorsCache[$entityClass] = $hydrator;
    }
}
