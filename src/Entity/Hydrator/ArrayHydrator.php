<?php

namespace Bdf\Prime\Entity\Hydrator;

use Bdf\Prime\Entity\ImportableInterface;

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
     * {@inheritdoc}
     */
    public function hydrate($object, array $data)
    {
        $privatePrefix = "\0" . get_class($object) . "\0";
        $privatePrefixLen = strlen($privatePrefix);

        foreach ((array) $object as $name => $property) {
            if (strpos($name, self::PROTECTED_PREFIX) === 0) {
                $name = substr($name, 3);
            } elseif (strpos($name, $privatePrefix) === 0) {
                $name = substr($name, $privatePrefixLen);
            }

            if (!array_key_exists($name, $data)) {
                continue;
            }

            $value = $data[$name];

            if ($property instanceof ImportableInterface && is_array($value)) {
                $property->import($value);
            } elseif (method_exists($object, 'set' . ucfirst($name))) {
                $object->{'set' . ucfirst($name)}($value);
            } else {
                $object->$name = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function extract($object, array $attributes = [])
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
}