<?php

namespace Bdf\Prime\Relations;

use InvalidArgumentException;

/**
 * Polymorph
 */
trait Polymorph
{
    /**
     * Map of entities
     *
     * @var array
     */
    protected $map = [];

    /**
     * The discriminator property name for polymorphic relation
     *
     * @var string
     */
    protected $discriminator;

    /**
     * The discriminator value for polymorphic relation
     *
     * @var int|string
     */
    protected $discriminatorValue;

    /**
     * Set the polymorphic map
     *
     * @param array $map
     *
     * @return $this
     */
    public function setMap(array $map)
    {
        $this->map = $map;

        return $this;
    }

    /**
     * Get the map from the discriminator value
     *
     * @param mixed $value   The discriminator value
     *
     * @return array
     *
     * @throws InvalidArgumentException   If the value has no map
     */
    public function map($value)
    {
        if (empty($this->map[$value])) {
            throw new InvalidArgumentException('Unknown discriminator type "'.$value.'"');
        }

        return $this->resolveEntity($this->map[$value]);
    }

    /**
     * Set the discriminator property name
     *
     * @param string $discriminator
     *
     * @return $this
     */
    public function setDiscriminator($discriminator)
    {
        $this->discriminator = $discriminator;

        return $this;
    }

    /**
     * Set the discriminator value
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setDiscriminatorValue($value)
    {
        $this->discriminatorValue = $value;

        return $this;
    }

    /**
     * Is the relation polymorphic
     *
     * @return bool
     */
    public function isPolymorphic()
    {
        return $this->discriminator !== null;
    }

    /**
     * Get the map from the discriminator value
     *
     * @param string $className
     *
     * @return mixed   The discriminator value
     *
     * @throws InvalidArgumentException   If the class name has no discriminator
     */
    public function discriminator($className)
    {
        foreach ($this->map as $type => &$value) {
            $this->resolveEntity($value);

            if ($value['entity'] === $className) {
                return $type;
            }
        }

        throw new InvalidArgumentException('Unknown map for the class "'.$className.'"');
    }

    /**
     * Resolve the entity name for meta relation
     *
     * @param string|array{entity:class-string,distantKey:string,?constraints:mixed} $value
     *
     * @return array{entity:class-string,distantKey:string,?constraints:mixed}
     */
    protected function resolveEntity(&$value): array
    {
        if (is_string($value)) {
            list($entity, $distantKey) = Relation::parseEntity($value);

            $value = [
                'entity'     => $entity,
                'distantKey' => $distantKey,
            ];
        }

        return $value;
    }

    /**
     * @param array $with
     *
     * @return array
     */
    protected function rearrangeWith(array $with): array
    {
        $rearrangedWith = [];

        foreach ($this->map as $discriminator => $meta) {
            $rearrangedWith[$discriminator] = [];
        }

        foreach ($with as $relationName => $relations) {
            $parts = explode('#', $relationName, 2);

            if (count($parts) > 1) {
                $rearrangedWith[$parts[0]][$parts[1]] = $relations;
            } else {
                foreach ($rearrangedWith as $discriminator => $values) {
                    $rearrangedWith[$discriminator][$relationName] = $relations;
                }
            }
        }

        return $rearrangedWith;
    }

    /**
     * @param array $without
     *
     * @return array
     */
    protected function rearrangeWithout(array $without): array
    {
        $rearranged = [];

        foreach ($this->map as $discriminator => $meta) {
            $rearranged[$discriminator] = [];
        }

        foreach ($without as $relationName) {
            $parts = explode('#', $relationName, 2);

            if (count($parts) > 1) {
                $rearranged[$parts[0]][] = $parts[1];
            } else {
                foreach ($rearranged as $discriminator => $values) {
                    $rearranged[$discriminator][] = $relationName;
                }
            }
        }

        return $rearranged;
    }

    /**
     * Get the discriminator value from an entity
     *
     * @param object $entity
     *
     * @return void
     */
    protected function updateDiscriminatorValue($entity): void
    {
        $this->discriminatorValue = $this->local->extractOne($entity, $this->discriminator);
    }
}
