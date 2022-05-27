<?php

namespace Bdf\Prime\Entity\Extensions;

use Bdf\Prime\Entity\ImportableInterface;

/**
 * ArrayInjector
 */
trait ArrayInjector
{
    /**
     * Import attributes into object
     *
     * The method will parse the attribute and will search for
     *  * if the attribute is an ImportableInterface the method will call the import method
     *  * a 'set' method
     *  * a valid attribute
     *
     * @param array $data
     *
     * @return void
     */
    public function import(array $data): void
    {
        if (empty($data)) {
            return;
        }

        foreach ($data as $attribute => $value) {
            $method = 'set' . ucfirst($attribute);
            $exists = property_exists($this, $attribute);

            if ($exists && $this->$attribute instanceof ImportableInterface && !$value instanceof ImportableInterface) {
                $this->$attribute->import($value);
            } elseif (method_exists($this, $method)) {
                $this->$method($value);
            } elseif ($exists) {
                $this->$attribute = $value;
            }
        }
    }

    /**
     * Export attributes or all entity in array
     *
     * @param list<string> $attributes
     *
     * @return array
     */
    public function export(array $attributes = []): array
    {
        $values = [];

        if ($attributes) {
            foreach ($attributes as $attribute) {
                if (!property_exists($this, $attribute)) {
                    continue;
                }

                $value = $this->$attribute;

                if ($value instanceof ImportableInterface) {
                    $values[$attribute] = $value->export();
                } else {
                    $values[$attribute] = $value;
                }
            }
        } else {
            foreach ($this as $attribute => $value) {
                if ($value instanceof ImportableInterface) {
                    //TODO : peut causer une recursion infinie
                    $values[$attribute] = $value->export();
                } else {
                    $values[$attribute] = $value;
                }
            }
        }

        return $values;
    }
}
